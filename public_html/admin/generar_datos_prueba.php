<?php
require_once '../../includes/db.php';

echo "<h1>🛠️ Generador de Datos de Prueba - Ventas con Detalles</h1>";
echo "<hr>";

// Verificar si ya hay datos
$ventasStmt = $conn->query("SELECT COUNT(*) FROM ventas");
$totalVentas = $ventasStmt->fetchColumn();

$detallesStmt = $conn->query("SELECT COUNT(*) FROM venta_detalles");
$totalDetalles = $detallesStmt->fetchColumn();

echo "<h2>📊 Estado Actual</h2>";
echo "<p><strong>Ventas:</strong> $totalVentas</p>";
echo "<p><strong>Detalles:</strong> $totalDetalles</p>";

// Verificar productos disponibles
$productosStmt = $conn->query("SELECT id, nombre, precio FROM productos LIMIT 5");
$productos = $productosStmt->fetchAll(PDO::FETCH_ASSOC);

if (count($productos) == 0) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; border-radius: 5px;'>";
    echo "<h3>❌ No hay productos</h3>";
    echo "<p>Primero necesitas productos en la base de datos antes de crear ventas.</p>";
    echo "</div>";
    exit;
}

// Verificar usuarios
$usuariosStmt = $conn->query("SELECT id, nombre FROM usuarios WHERE rol IN ('vendedor', 'jefe', 'admin') LIMIT 3");
$usuarios = $usuariosStmt->fetchAll(PDO::FETCH_ASSOC);

if (count($usuarios) == 0) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; border-radius: 5px;'>";
    echo "<h3>❌ No hay usuarios</h3>";
    echo "<p>Necesitas usuarios en la base de datos antes de crear ventas.</p>";
    echo "</div>";
    exit;
}

echo "<h2>📦 Productos Disponibles</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th></tr>";
foreach ($productos as $prod) {
    echo "<tr><td>{$prod['id']}</td><td>{$prod['nombre']}</td><td>\${$prod['precio']}</td></tr>";
}
echo "</table>";

echo "<h2>👥 Usuarios Disponibles</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Nombre</th></tr>";
foreach ($usuarios as $user) {
    echo "<tr><td>{$user['id']}</td><td>{$user['nombre']}</td></tr>";
}
echo "</table>";

// Generar datos de prueba si se solicita
if (isset($_GET['generar']) && $_GET['generar'] == '1') {
    echo "<h2>🔧 Generando Datos de Prueba...</h2>";
    
    try {
        $conn->beginTransaction();
        
        // Crear 3 ventas de prueba
        for ($i = 1; $i <= 3; $i++) {
            // Datos de la venta
            $usuario_id = $usuarios[array_rand($usuarios)]['id'];
            $metodo_pago = ['efectivo', 'tarjeta', 'transferencia'][array_rand(['efectivo', 'tarjeta', 'transferencia'])];
            $tipo_documento = ['boleta', 'factura'][array_rand(['boleta', 'factura'])];
            $numero_documento = strtoupper($tipo_documento) . '-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            // Insertar venta
            $ventaStmt = $conn->prepare("
                INSERT INTO ventas (usuario_id, total, metodo_pago, tipo_documento, numero_documento, fecha, sesion_caja_id) 
                VALUES (?, ?, ?, ?, ?, NOW(), NULL)
            ");
            
            // Calcular total primero
            $total = 0;
            $itemsComprados = [];
            
            // Seleccionar 2-3 productos aleatorios
            $numItems = rand(2, 3);
            $productosSeleccionados = array_rand($productos, min($numItems, count($productos)));
            if (!is_array($productosSeleccionados)) {
                $productosSeleccionados = [$productosSeleccionados];
            }
            
            foreach ($productosSeleccionados as $prodIndex) {
                $producto = $productos[$prodIndex];
                $cantidad = rand(1, 3);
                $precio_unitario = $producto['precio'];
                $subtotal = $precio_unitario * $cantidad;
                $total += $subtotal;
                
                $itemsComprados[] = [
                    'producto_id' => $producto['id'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio_unitario,
                    'subtotal' => $subtotal,
                    'nombre' => $producto['nombre']
                ];
            }
            
            // Insertar la venta con el total calculado
            $ventaStmt->execute([$usuario_id, $total, $metodo_pago, $tipo_documento, $numero_documento]);
            $venta_id = $conn->lastInsertId();
            
            echo "<h3>✅ Venta $i creada (ID: $venta_id)</h3>";
            echo "<p><strong>Usuario:</strong> $usuario_id | <strong>Total:</strong> \$$total | <strong>Método:</strong> $metodo_pago | <strong>Documento:</strong> $numero_documento</p>";
            
            // Insertar detalles
            echo "<h4>📦 Productos vendidos:</h4>";
            echo "<ul>";
            foreach ($itemsComprados as $item) {
                $detalleStmt = $conn->prepare("
                    INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $detalleStmt->execute([
                    $venta_id,
                    $item['producto_id'],
                    $item['cantidad'],
                    $item['precio_unitario'],
                    $item['subtotal']
                ]);
                
                echo "<li>{$item['nombre']} - Cantidad: {$item['cantidad']} - Precio: \${$item['precio_unitario']} - Subtotal: \${$item['subtotal']}</li>";
            }
            echo "</ul>";
        }
        
        $conn->commit();
        echo "<div style='background: #e6ffe6; padding: 15px; border: 1px solid #00aa00; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🎉 ¡Datos de prueba creados exitosamente!</h3>";
        echo "<p>Se han creado 3 ventas con sus respectivos detalles de productos.</p>";
        echo "<p><a href='ventas.php'>Ver en el registro de ventas</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; border-radius: 5px;'>";
        echo "<h3>❌ Error al crear datos</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
} else {
    echo "<h2>🎯 Acción Requerida</h2>";
    echo "<div style='background: #e6f3ff; padding: 15px; border: 1px solid #0066cc; border-radius: 5px;'>";
    echo "<h3>¿Qué quieres hacer?</h3>";
    echo "<p><a href='?generar=1' style='background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Generar Datos de Prueba</a></p>";
    echo "<p><small>Esto creará 3 ventas de ejemplo con productos para probar el sistema de detalles.</small></p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>🔗 Enlaces útiles:</strong></p>";
echo "<ul>";
echo "<li><a href='check_database.php'>Verificar Estado de BD</a></li>";
echo "<li><a href='ventas.php'>Ver Registro de Ventas</a></li>";
echo "<li><a href='../modules/pos/'>Módulo POS</a></li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f0f0f0; }
a { color: #0066cc; }
</style>
