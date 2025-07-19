<?php
// Controlador para generar PDFs de boletas y facturas del POS

// --- NO debe haber salida previa antes de los headers ---
// Limpiar cualquier salida previa antes de los headers
if (ob_get_level()) {
    while (ob_get_level()) ob_end_clean();
}
// Desactivar salida de errores en PDF (solo log)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
session_start();
require_once '../../../includes/auth_middleware.php';
require_once '../../../includes/db.php';
require_once '../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PDFController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function generateSalePDF($ventaId, $download = true) {
        try {
            // Obtener configuración del IVA
            $stmt = $this->db->prepare("SELECT valor FROM configuracion WHERE clave = 'iva_porcentaje'");
            $stmt->execute();
            $ivaRate = $stmt->fetchColumn() ?: 19; // Default 19% si no existe

            // Obtener datos de la venta
            $stmt = $this->db->prepare("
                SELECT v.*, u.nombre as vendedor_nombre,
                       c.rut_empresa, c.razon_social, c.direccion, 
                       c.telefono, c.correo, c.rut as cliente_rut, c.nombre as cliente_nombre
                FROM ventas v 
                LEFT JOIN usuarios u ON v.usuario_id = u.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE v.id = ?
            ");
            $stmt->execute([$ventaId]);
            $venta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$venta) {
                throw new Exception("Venta no encontrada");
            }

            // Obtener detalles de la venta
            $stmt = $this->db->prepare("
                SELECT vd.*, p.nombre as producto_nombre, p.codigo_barras as producto_codigo
                FROM venta_detalles vd
                JOIN productos p ON vd.producto_id = p.id
                WHERE vd.venta_id = ?
                ORDER BY vd.id ASC
            ");
            $stmt->execute([$ventaId]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Generar HTML del PDF
            $html = $this->generatePDFHTML($venta, $detalles, $ivaRate);

            // Configurar DomPDF
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Nombre del archivo
            $documentType = ucfirst($venta['tipo_documento']);
            $filename = "{$documentType}_{$venta['numero_documento']}.pdf";

            // Siempre forzar descarga en todos los navegadores
            if (ob_get_length()) ob_end_clean();
            header_remove();
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');
            echo $dompdf->output();
            exit;

        } catch (Exception $e) {
            error_log("Error generando PDF: " . $e->getMessage());
            if ($download) {
                http_response_code(500);
                echo json_encode(['error' => 'Error generando PDF: ' . $e->getMessage()]);
            } else {
                throw $e;
            }
        }
    }
    
    private function generatePDFHTML($venta, $detalles, $ivaRate) {
        // Formatear fecha
        $fecha = date('d/m/Y H:i', isset($venta['fecha']) ? strtotime($venta['fecha']) : time());
        
        // Determinar tipo de documento
        $documentType = (isset($venta['tipo_documento']) && $venta['tipo_documento'] === 'factura') ? 'FACTURA' : 'BOLETA';
        
        // Calcular totales
        $total = isset($venta['total']) ? floatval($venta['total']) : 0;
        $subtotal = $total / (1 + ($ivaRate / 100));
        $ivaAmount = $total - $subtotal;
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            margin: 20px; 
            line-height: 1.4; 
            color: #333;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #333; 
            padding-bottom: 20px; 
        }
        .company-name { 
            font-size: 24px; 
            font-weight: bold; 
            color: #333; 
            margin-bottom: 5px; 
        }
        .company-info { 
            font-size: 12px; 
            color: #666; 
        }
        .document-type { 
            font-size: 18px; 
            font-weight: bold; 
            color: #333; 
            margin: 20px 0; 
            text-align: center; 
            background-color: #f0f0f0; 
            padding: 15px; 
            border-radius: 8px;
            border: 2px solid #333;
        }
        .sale-info { 
            margin: 20px 0; 
            background-color: #f9f9f9; 
            padding: 15px; 
            border-radius: 8px; 
            border: 1px solid #ddd;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .info-row {
            display: table-row;
        }
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            padding: 5px 0;
        }
        .info-right {
            text-align: right;
        }
        .info-label { 
            font-weight: bold; 
            color: #333; 
        }
        .info-value {
            color: #555;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            border: 2px solid #333;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        th { 
            background-color: #f5f5f5; 
            font-weight: bold; 
            color: #333; 
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-section { 
            margin-top: 20px; 
            text-align: right; 
            background-color: #f9f9f9; 
            padding: 20px; 
            border-radius: 8px; 
            border: 1px solid #ddd;
        }
        .total-label {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }
        .customer-section {
            margin: 20px 0;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">Sistema Bazar</div>
        <div class="company-info">
            Punto de Venta Electrónico<br>
            Santiago, Chile
        </div>
    </div>

    <div class="document-type">' . $documentType . ' N° ' . htmlspecialchars(isset($venta['numero_documento']) ? $venta['numero_documento'] : 'N/A') . '</div>

    <div class="sale-info">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Fecha:</span> 
                    <span class="info-value">' . $fecha . '</span>
                </div>
                <div class="info-right">
                    <span class="info-label">Vendedor:</span> 
                    <span class="info-value">' . htmlspecialchars($venta['vendedor_nombre'] ?: 'N/A') . '</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Método de Pago:</span> 
                    <span class="info-value">' . ucfirst($venta['metodo_pago']) . '</span>
                </div>
                <div class="info-right">
                    <span class="info-label">Total:</span> 
                    <span class="info-value">$' . number_format($venta['total'], 2, ',', '.') . '</span>
                </div>
            </div>
        </div>
    </div>';

        // Información del cliente para facturas
        if ((isset($venta['tipo_documento']) && $venta['tipo_documento'] === 'factura') && !empty($venta['cliente_id'])) {
            $html .= '
    <div class="customer-section">
        <div class="info-label" style="margin-bottom: 10px; font-size: 14px;">Datos del Cliente:</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">RUT:</span> 
                    <span class="info-value">' . htmlspecialchars(isset($venta['rut_empresa']) && $venta['rut_empresa'] ? $venta['rut_empresa'] : 'N/A') . '</span>
                </div>
                <div class="info-right">
                    <span class="info-label">Razón Social:</span> 
                    <span class="info-value">' . htmlspecialchars(isset($venta['razon_social']) && $venta['razon_social'] ? $venta['razon_social'] : 'N/A') . '</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Dirección:</span> 
                    <span class="info-value">' . htmlspecialchars(isset($venta['direccion']) && $venta['direccion'] ? $venta['direccion'] : 'N/A') . '</span>
                </div>
                <div class="info-right">
                    <span class="info-label">Teléfono:</span> 
                    <span class="info-value">' . htmlspecialchars(isset($venta['telefono']) && $venta['telefono'] ? $venta['telefono'] : 'N/A') . '</span>
                </div>
            </div>';
            
            if ((isset($venta['cliente_rut']) && $venta['cliente_rut']) || (isset($venta['cliente_nombre']) && $venta['cliente_nombre'])) {
                $html .= '
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Contacto RUT:</span> 
                    <span class="info-value">' . htmlspecialchars(isset($venta['cliente_rut']) && $venta['cliente_rut'] ? $venta['cliente_rut'] : 'N/A') . '</span>
                </div>
                <div class="info-right">
                    <span class="info-label">Contacto Nombre:</span> 
                    <span class="info-value">' . htmlspecialchars(isset($venta['cliente_nombre']) && $venta['cliente_nombre'] ? $venta['cliente_nombre'] : 'N/A') . '</span>
                </div>
            </div>';
            }
            
            $html .= '
        </div>
    </div>';
        }

        // Tabla de productos
        $html .= '
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Código</th>
                <th style="width: 45%;">Producto</th>
                <th style="width: 10%;">Cant.</th>
                <th style="width: 15%;">Precio Unit.</th>
                <th style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($detalles as $detalle) {
            $html .= '
            <tr>
                <td class="text-center">' . htmlspecialchars(isset($detalle['producto_codigo']) && $detalle['producto_codigo'] ? $detalle['producto_codigo'] : 'N/A') . '</td>
                <td>' . htmlspecialchars(isset($detalle['producto_nombre']) ? $detalle['producto_nombre'] : 'N/A') . '</td>
                <td class="text-center">' . (isset($detalle['cantidad']) ? $detalle['cantidad'] : 'N/A') . '</td>
                <td class="text-right">$' . (isset($detalle['precio']) ? number_format($detalle['precio'], 2, ',', '.') : '0,00') . '</td>
                <td class="text-right">$' . (isset($detalle['subtotal']) ? number_format($detalle['subtotal'], 2, ',', '.') : '0,00') . '</td>
            </tr>';
        }

        $html .= '
        </tbody>
    </table>

    <div class="total-section">
        <div style="margin-bottom: 10px;">
            <div style="display: table; width: 100%;">
                <div style="display: table-row;">
                    <div style="display: table-cell; text-align: right; padding: 5px 0;">
                        <span style="font-size: 14px;">Subtotal (sin IVA):</span>
                    </div>
                    <div style="display: table-cell; text-align: right; width: 120px; padding: 5px 0 5px 20px;">
                        <span style="font-size: 14px;">$' . number_format($subtotal, 2, ',', '.') . '</span>
                    </div>
                </div>
                <div style="display: table-row;">
                    <div style="display: table-cell; text-align: right; padding: 5px 0;">
                        <span style="font-size: 14px;">IVA (' . $ivaRate . '%):</span>
                    </div>
                    <div style="display: table-cell; text-align: right; width: 120px; padding: 5px 0 5px 20px;">
                        <span style="font-size: 14px;">$' . number_format($ivaAmount, 2, ',', '.') . '</span>
                    </div>
                </div>
                <div style="display: table-row; border-top: 2px solid #333; padding-top: 10px;">
                    <div style="display: table-cell; text-align: right; padding: 10px 0 5px 0;">
                        <span class="total-label" style="font-size: 18px;">TOTAL:</span>
                    </div>
                    <div style="display: table-cell; text-align: right; width: 120px; padding: 10px 0 5px 20px;">
                        <span class="total-amount" style="font-size: 20px;">$' . number_format($total, 2, ',', '.') . '</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Documento generado electrónicamente el ' . date('d/m/Y H:i') . '</p>
        <p>Gracias por su compra</p>
    </div>
</body>
</html>';

        return $html;
    }
}

// Manejo de solicitudes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['venta_id'])) {
    try {
        $pdfController = new PDFController($conn);
        $pdfController->generateSalePDF($_GET['venta_id'], true);
    } catch (Exception $e) {
        error_log("Error en PDF Controller: " . $e->getMessage());
        http_response_code(500);
        echo "Error generando PDF";
    }
} else {
    http_response_code(400);
    echo "Parámetros inválidos";
}
?>
