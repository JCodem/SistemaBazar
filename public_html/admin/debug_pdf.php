<?php
require_once '../../includes/db.php';
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "<h1>🔍 Debug PDF - Visualización de Factura</h1>";
echo "<hr>";

// Get venta ID from URL
$venta_id = (int)($_GET['venta_id'] ?? 1);

echo "<h2>🎯 Depurando Venta ID: $venta_id</h2>";

// Get sale info
$ventaStmt = $conn->prepare("
    SELECT v.*, u.nombre as vendedor, u.correo as vendedor_email 
    FROM ventas v 
    JOIN usuarios u ON v.usuario_id = u.id 
    WHERE v.id = ?
");
$ventaStmt->execute([$venta_id]);
$venta = $ventaStmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo "<div style='color: red;'>❌ Venta no encontrada con ID: $venta_id</div>";
    
    // Show available sales
    echo "<h3>Ventas disponibles:</h3>";
    $availableStmt = $conn->query("SELECT id, numero_documento, fecha FROM ventas ORDER BY id DESC LIMIT 10");
    $available = $availableStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($available) {
        echo "<ul>";
        foreach ($available as $av) {
            echo "<li><a href='?venta_id={$av['id']}'>ID {$av['id']} - {$av['numero_documento']} - {$av['fecha']}</a></li>";
        }
        echo "</ul>";
    }
    exit;
}

echo "<h3>✅ Información de la Venta:</h3>";
echo "<pre>";
print_r($venta);
echo "</pre>";

// Get sale details
$detallesStmt = $conn->prepare("
    SELECT vd.id, vd.venta_id, vd.producto_id, vd.cantidad, 
           vd.precio_unitario, vd.subtotal,
           p.nombre as producto_nombre
    FROM venta_detalles vd
    LEFT JOIN productos p ON vd.producto_id = p.id
    WHERE vd.venta_id = ?
    ORDER BY COALESCE(p.nombre, 'Producto eliminado')
");
$detallesStmt->execute([$venta_id]);
$detalles = $detallesStmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>📦 Detalles de Productos (" . count($detalles) . " items):</h3>";
if (!empty($detalles)) {
    echo "<pre>";
    print_r($detalles);
    echo "</pre>";
} else {
    echo "<div style='color: orange;'>⚠️ No hay detalles de productos para esta venta</div>";
}

// Test PDF generation modes
if (isset($_GET['mode'])) {
    $mode = $_GET['mode'];
    
    if ($mode === 'html') {
        echo "<h2>🌐 Vista HTML (para debug)</h2>";
        echo "<div style='border: 2px solid #ccc; padding: 20px; background: white; color: black;'>";
        
        // Generate the same HTML that would go to PDF
        include 'pdf_template.php';
        
        echo "</div>";
    }
    
    if ($mode === 'pdf') {
        echo "<h2>📄 Generando PDF...</h2>";
        
        // Redirect to actual PDF generation
        header("Location: ?export_venta=$venta_id");
        exit;
    }
}

echo "<hr>";
echo "<h2>🛠️ Opciones de Debug</h2>";
echo "<p><a href='?venta_id=$venta_id&mode=html' target='_blank' style='background: #007cba; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Ver como HTML</a></p>";
echo "<p><a href='?venta_id=$venta_id&mode=pdf' target='_blank' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Generar PDF</a></p>";
echo "<p><a href='ventas.php' style='background: #28a745; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Volver a Ventas</a></p>";

// Show other sales for testing
echo "<h3>🔗 Otras ventas para probar:</h3>";
$otherSales = $conn->query("SELECT id, numero_documento, tipo_documento FROM ventas WHERE id != $venta_id ORDER BY id DESC LIMIT 5");
$others = $otherSales->fetchAll(PDO::FETCH_ASSOC);

if ($others) {
    echo "<ul>";
    foreach ($others as $other) {
        echo "<li><a href='?venta_id={$other['id']}'>{$other['tipo_documento']} {$other['numero_documento']} (ID: {$other['id']})</a></li>";
    }
    echo "</ul>";
}
?>
