<?php
require_once '../../includes/db.php';

echo "<h1>🔍 Análisis Completo de Base de Datos - Sistema Ventas</h1>";
echo "<hr>";

// 1. Verificar si hay ventas
echo "<h2>📊 1. Ventas en la Base de Datos</h2>";
try {
    $ventasStmt = $conn->query("SELECT COUNT(*) as total FROM ventas");
    $totalVentas = $ventasStmt->fetchColumn();
    echo "<p><strong>Total de ventas:</strong> $totalVentas</p>";
    
    if ($totalVentas > 0) {
        $recentVentas = $conn->query("SELECT id, numero_documento, fecha, total FROM ventas ORDER BY id DESC LIMIT 5");
        $ventas = $recentVentas->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Últimas 5 ventas:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Número Doc</th><th>Fecha</th><th>Total</th></tr>";
        foreach ($ventas as $venta) {
            echo "<tr>";
            echo "<td>{$venta['id']}</td>";
            echo "<td>{$venta['numero_documento']}</td>";
            echo "<td>{$venta['fecha']}</td>";
            echo "<td>\${$venta['total']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// 2. Verificar si hay detalles de ventas
echo "<h2>📦 2. Detalles de Ventas en la Base de Datos</h2>";
try {
    $detallesStmt = $conn->query("SELECT COUNT(*) as total FROM venta_detalles");
    $totalDetalles = $detallesStmt->fetchColumn();
    echo "<p><strong>Total de detalles de ventas:</strong> $totalDetalles</p>";
    
    if ($totalDetalles > 0) {
        $recentDetalles = $conn->query("
            SELECT vd.*, p.nombre as producto_nombre 
            FROM venta_detalles vd 
            LEFT JOIN productos p ON vd.producto_id = p.id 
            ORDER BY vd.id DESC 
            LIMIT 10
        ");
        $detalles = $recentDetalles->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Últimos 10 detalles:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Venta ID</th><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr>";
        foreach ($detalles as $detalle) {
            echo "<tr>";
            echo "<td>{$detalle['id']}</td>";
            echo "<td>{$detalle['venta_id']}</td>";
            echo "<td>{$detalle['producto_nombre']}</td>";
            echo "<td>{$detalle['cantidad']}</td>";
            echo "<td>\${$detalle['precio_unitario']}</td>";
            echo "<td>\${$detalle['subtotal']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; border-radius: 5px;'>";
        echo "<h3 style='color: #ff0000;'>⚠️ PROBLEMA DETECTADO</h3>";
        echo "<p><strong>No hay registros en la tabla venta_detalles.</strong></p>";
        echo "<p>Esto significa que cuando se realizan ventas, solo se guarda la información general en la tabla 'ventas', pero no se guardan los productos específicos que se vendieron.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// 3. Verificar productos
echo "<h2>🛍️ 3. Productos Disponibles</h2>";
try {
    $productosStmt = $conn->query("SELECT COUNT(*) as total FROM productos");
    $totalProductos = $productosStmt->fetchColumn();
    echo "<p><strong>Total de productos:</strong> $totalProductos</p>";
    
    if ($totalProductos > 0) {
        $productos = $conn->query("SELECT id, nombre, precio, stock FROM productos LIMIT 5");
        $prods = $productos->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Primeros 5 productos:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th></tr>";
        foreach ($prods as $prod) {
            echo "<tr>";
            echo "<td>{$prod['id']}</td>";
            echo "<td>{$prod['nombre']}</td>";
            echo "<td>\${$prod['precio']}</td>";
            echo "<td>{$prod['stock']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// 4. Verificar estructura de venta_detalles
echo "<h2>🔧 4. Estructura de la Tabla venta_detalles</h2>";
try {
    $structureStmt = $conn->query("DESCRIBE venta_detalles");
    $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($structure as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// 5. Verificar ventas que SÍ tienen detalles
echo "<h2>✅ 5. Ventas con Detalles (si existen)</h2>";
try {
    $ventasConDetalles = $conn->query("
        SELECT v.id, v.numero_documento, v.total, COUNT(vd.id) as num_items
        FROM ventas v 
        LEFT JOIN venta_detalles vd ON v.id = vd.venta_id 
        GROUP BY v.id 
        HAVING num_items > 0 
        ORDER BY v.id DESC 
        LIMIT 5
    ");
    $ventasConDet = $ventasConDetalles->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($ventasConDet) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Venta ID</th><th>Número Doc</th><th>Total</th><th>Items</th><th>Acción</th></tr>";
        foreach ($ventasConDet as $venta) {
            echo "<tr>";
            echo "<td>{$venta['id']}</td>";
            echo "<td>{$venta['numero_documento']}</td>";
            echo "<td>\${$venta['total']}</td>";
            echo "<td>{$venta['num_items']}</td>";
            echo "<td><a href='debug_venta_detalles.php?venta_id={$venta['id']}' target='_blank'>Ver detalles</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>❌ No hay ventas con detalles en la base de datos.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// 6. Diagnóstico final
echo "<h2>🎯 6. Diagnóstico y Recomendaciones</h2>";
try {
    $totalVentasQuery = $conn->query("SELECT COUNT(*) FROM ventas");
    $totalVentas = $totalVentasQuery->fetchColumn();
    
    $totalDetallesQuery = $conn->query("SELECT COUNT(*) FROM venta_detalles");
    $totalDetalles = $totalDetallesQuery->fetchColumn();
    
    echo "<div style='background: #e6f3ff; padding: 15px; border: 1px solid #0066cc; border-radius: 5px;'>";
    echo "<h3>📊 Resumen:</h3>";
    echo "<ul>";
    echo "<li><strong>Ventas registradas:</strong> $totalVentas</li>";
    echo "<li><strong>Detalles de productos:</strong> $totalDetalles</li>";
    echo "</ul>";
    
    if ($totalVentas > 0 && $totalDetalles == 0) {
        echo "<div style='background: #ffe6e6; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4 style='color: #cc0000;'>🚨 PROBLEMA IDENTIFICADO:</h4>";
        echo "<p><strong>Las ventas se están registrando pero SIN los detalles de productos.</strong></p>";
        echo "<p>Esto significa que el sistema POS no está guardando qué productos específicos se vendieron.</p>";
        echo "</div>";
        
        echo "<div style='background: #e6ffe6; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4 style='color: #006600;'>💡 SOLUCIONES:</h4>";
        echo "<ol>";
        echo "<li><strong>Revisar el módulo POS:</strong> Verificar que cuando se procesa una venta, también se inserten los registros en venta_detalles</li>";
        echo "<li><strong>Crear datos de prueba:</strong> Puedo crear algunos registros de ejemplo para probar el sistema</li>";
        echo "<li><strong>Verificar el código de ventas:</strong> Revisar el archivo que procesa las ventas en el POS</li>";
        echo "</ol>";
        echo "</div>";
    } elseif ($totalVentas > 0 && $totalDetalles > 0) {
        echo "<div style='background: #e6ffe6; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4 style='color: #006600;'>✅ SISTEMA FUNCIONANDO:</h4>";
        echo "<p>Las ventas tienen detalles de productos asociados.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3e6; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4 style='color: #cc6600;'>📝 SIN DATOS:</h4>";
        echo "<p>No hay ventas registradas en el sistema aún.</p>";
        echo "</div>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error en diagnóstico: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>🔗 Enlaces útiles:</strong></p>";
echo "<ul>";
echo "<li><a href='ventas.php'>Volver a Ventas</a></li>";
echo "<li><a href='debug_venta_detalles.php'>Debug de Venta Específica</a></li>";
echo "<li><a href='../modules/pos/'>Módulo POS</a></li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { width: 100%; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f0f0f0; }
</style>
