<?php
// Script de prueba para verificar generación de PDF
session_start();
require_once 'includes/db.php';
require_once 'vendor/autoload.php';

try {
    // Verificar si DomPDF está disponible
    if (class_exists('Dompdf\Dompdf')) {
        echo "✅ DomPDF está correctamente instalado<br>";
    } else {
        echo "❌ DomPDF no encontrado<br>";
    }
    
    // Verificar última venta
    $stmt = $conn->query("SELECT v.id, v.numero_documento, v.tipo_documento, v.total FROM ventas v ORDER BY v.id DESC LIMIT 1");
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($venta) {
        echo "✅ Última venta encontrada: ID {$venta['id']}, {$venta['tipo_documento']} #{$venta['numero_documento']}<br>";
        echo "<a href='public_html/modules/pos/pdf_controller.php?venta_id={$venta['id']}' target='_blank' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; display: inline-block;'>Descargar PDF de Prueba</a>";
    } else {
        echo "❌ No hay ventas en la base de datos<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
