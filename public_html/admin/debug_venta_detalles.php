<?php
require_once '../../includes/db.php';

// Script de depuración para verificar los detalles de ventas
$venta_id = $_GET['venta_id'] ?? 1; // Usar ID 1 por defecto o el que pases por URL

echo "<h2>Debug - Detalles de Venta ID: $venta_id</h2>";

// 1. Verificar que la venta existe
echo "<h3>1. Información de la venta:</h3>";
$ventaStmt = $conn->prepare("SELECT * FROM ventas WHERE id = ?");
$ventaStmt->execute([$venta_id]);
$venta = $ventaStmt->fetch(PDO::FETCH_ASSOC);

if ($venta) {
    echo "<pre>";
    print_r($venta);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Venta no encontrada</p>";
    exit;
}

// 2. Verificar estructura de tabla venta_detalles
echo "<h3>2. Estructura de tabla venta_detalles:</h3>";
try {
    $structureStmt = $conn->query("DESCRIBE venta_detalles");
    $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al obtener estructura: " . $e->getMessage() . "</p>";
}

// 3. Verificar si existen detalles para esta venta
echo "<h3>3. Detalles sin JOIN (solo venta_detalles):</h3>";
$detallesStmt = $conn->prepare("SELECT * FROM venta_detalles WHERE venta_id = ?");
$detallesStmt->execute([$venta_id]);
$detalles = $detallesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($detalles) {
    echo "<p style='color: green;'>✅ Encontrados " . count($detalles) . " detalles</p>";
    echo "<pre>";
    print_r($detalles);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ No se encontraron detalles para esta venta</p>";
}

// 4. Verificar estructura de tabla productos
echo "<h3>4. Estructura de tabla productos:</h3>";
try {
    $productStructureStmt = $conn->query("DESCRIBE productos");
    $productStructure = $productStructureStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($productStructure as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al obtener estructura de productos: " . $e->getMessage() . "</p>";
}

// 5. Verificar productos existentes
echo "<h3>5. Algunos productos existentes:</h3>";
try {
    $productStmt = $conn->query("SELECT id, nombre, precio FROM productos LIMIT 5");
    $productos = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    if ($productos) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th></tr>";
        foreach ($productos as $prod) {
            echo "<tr>";
            echo "<td>{$prod['id']}</td>";
            echo "<td>{$prod['nombre']}</td>";
            echo "<td>{$prod['precio']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No hay productos en la base de datos</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al obtener productos: " . $e->getMessage() . "</p>";
}

// 6. Intentar JOIN manual
echo "<h3>6. JOIN manual entre venta_detalles y productos:</h3>";
if (!empty($detalles)) {
    foreach ($detalles as $detalle) {
        $producto_id = $detalle['producto_id'];
        echo "<h4>Detalle con producto_id: $producto_id</h4>";
        
        $prodStmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
        $prodStmt->execute([$producto_id]);
        $producto = $prodStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($producto) {
            echo "<p style='color: green;'>✅ Producto encontrado:</p>";
            echo "<pre>";
            print_r($producto);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Producto con ID $producto_id no encontrado</p>";
        }
    }
}

// 7. Probar la consulta original del modal
echo "<h3>7. Consulta original del modal:</h3>";
try {
    $originalStmt = $conn->prepare("
        SELECT vd.*, p.nombre as producto_nombre, p.precio as precio_unitario
        FROM venta_detalles vd
        LEFT JOIN productos p ON vd.producto_id = p.id
        WHERE vd.venta_id = ?
        ORDER BY p.nombre
    ");
    $originalStmt->execute([$venta_id]);
    $originalResult = $originalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($originalResult) {
        echo "<p style='color: green;'>✅ Consulta original funcionó. Resultados:</p>";
        echo "<pre>";
        print_r($originalResult);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ La consulta original no devolvió resultados</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error en consulta original: " . $e->getMessage() . "</p>";
}

// 8. Verificar todas las ventas disponibles
echo "<h3>8. Todas las ventas disponibles:</h3>";
try {
    $allVentasStmt = $conn->query("SELECT id, numero_documento, fecha, total FROM ventas ORDER BY id DESC LIMIT 10");
    $allVentas = $allVentasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allVentas) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Número Doc</th><th>Fecha</th><th>Total</th><th>Acción</th></tr>";
        foreach ($allVentas as $v) {
            echo "<tr>";
            echo "<td>{$v['id']}</td>";
            echo "<td>{$v['numero_documento']}</td>";
            echo "<td>{$v['fecha']}</td>";
            echo "<td>\${$v['total']}</td>";
            echo "<td><a href='?venta_id={$v['id']}'>Debug esta venta</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No hay ventas en la base de datos</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al obtener ventas: " . $e->getMessage() . "</p>";
}

// 9. Verificar datos en venta_detalles para todas las ventas
echo "<h3>9. Resumen de venta_detalles:</h3>";
try {
    $allDetallesStmt = $conn->query("SELECT venta_id, COUNT(*) as cantidad_items FROM venta_detalles GROUP BY venta_id ORDER BY venta_id DESC LIMIT 10");
    $allDetalles = $allDetallesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allDetalles) {
        echo "<table border='1'>";
        echo "<tr><th>Venta ID</th><th>Cantidad de Items</th></tr>";
        foreach ($allDetalles as $d) {
            echo "<tr>";
            echo "<td>{$d['venta_id']}</td>";
            echo "<td>{$d['cantidad_items']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No hay detalles en la tabla venta_detalles</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al obtener resumen de detalles: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Prueba este archivo visitando: debug_venta_detalles.php?venta_id=X</strong> (reemplaza X con un ID de venta válido)</p>";
?>
