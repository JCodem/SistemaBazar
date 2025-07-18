<?php
// Script de prueba para el PDF con IVA
require 'includes/db.php';

try {
    // Obtener la última venta
    $stmt = $conn->query("SELECT id, numero_documento, tipo_documento, total FROM ventas ORDER BY id DESC LIMIT 1");
    $venta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($venta) {
        echo "<h2>Prueba de PDF con IVA</h2>";
        echo "<p><strong>Última venta:</strong> {$venta['tipo_documento']} #{$venta['numero_documento']} - \${$venta['total']}</p>";
        echo "<a href='public_html/modules/pos/pdf_controller.php?venta_id={$venta['id']}' target='_blank' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>📄 Descargar PDF (Nueva versión con IVA)</a>";
        
        // También mostrar enlace para crear nueva venta de prueba
        echo "<br><br>";
        echo "<a href='public_html/modules/pos/views/pos.php' target='_blank' style='background: #16a34a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block;'>🛒 Crear Nueva Venta (POS)</a>";
        
        // Mostrar información del IVA configurado
        $stmt = $conn->query("SELECT valor FROM configuracion WHERE clave = 'iva_porcentaje'");
        $iva = $stmt->fetchColumn();
        echo "<p><strong>IVA configurado:</strong> {$iva}%</p>";
        
        if ($iva) {
            $subtotal = $venta['total'] / (1 + ($iva / 100));
            $ivaAmount = $venta['total'] - $subtotal;
            echo "<p><strong>Desglose de la última venta:</strong></p>";
            echo "<ul>";
            echo "<li>Subtotal (sin IVA): $" . number_format($subtotal, 2) . "</li>";
            echo "<li>IVA ({$iva}%): $" . number_format($ivaAmount, 2) . "</li>";
            echo "<li>Total: $" . number_format($venta['total'], 2) . "</li>";
            echo "</ul>";
        }
        
    } else {
        echo "<p>❌ No hay ventas en la base de datos. <a href='public_html/modules/pos/views/pos.php'>Crear una venta</a></p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
