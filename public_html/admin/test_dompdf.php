<?php
// Test básico de DomPDF
require_once '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "<h1>🧪 Test de DomPDF</h1>";

try {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $dompdf = new Dompdf($options);
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .test { background: #e6f3ff; padding: 20px; border: 2px solid #0066cc; }
        </style>
    </head>
    <body>
        <h1>Test PDF Básico</h1>
        <div class="test">
            <p>Si puedes ver este PDF, DomPDF está funcionando correctamente.</p>
            <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
        </div>
    </body>
    </html>';
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="test_dompdf.pdf"');
    echo $dompdf->output();
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h2>❌ Error en DomPDF:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>
