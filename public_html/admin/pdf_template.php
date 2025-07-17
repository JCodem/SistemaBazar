<?php
// Template HTML para el PDF - usado tanto para debug como para generación real
// Variables esperadas: $venta, $detalles

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
        .total-row { 
            font-size: 14px; 
            margin: 8px 0; 
            color: #555;
        }
        .grand-total { 
            font-size: 20px; 
            font-weight: bold; 
            border-top: 2px solid #333; 
            padding-top: 15px; 
            margin-top: 15px; 
            color: #333;
        }
        .footer { 
            margin-top: 40px; 
            text-align: center; 
            font-size: 10px; 
            color: #666; 
            border-top: 1px solid #ddd; 
            padding-top: 20px; 
        }
        .no-products { 
            text-align: center; 
            padding: 30px; 
            background-color: #fff3cd; 
            border: 2px solid #ffeaa7; 
            color: #856404; 
            border-radius: 8px;
            margin: 20px 0;
        }
        .debug-info {
            background-color: #e9ecef;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-size: 10px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">SISTEMA BAZAR</div>
        <div class="company-info">Sistema de Gestión de Ventas y POS</div>
        <div class="company-info">RUT: 12.345.678-9 • Teléfono: +56 9 1234 5678</div>
    </div>
    
    <div class="document-type">' . strtoupper($venta['tipo_documento'] ?? 'DOCUMENTO') . ' N° ' . htmlspecialchars($venta['numero_documento'] ?? 'N/A') . '</div>
    
    <div class="sale-info">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Fecha:</span> 
                    <span class="info-value">' . (isset($venta['fecha']) ? date('d/m/Y H:i', strtotime($venta['fecha'])) : 'N/A') . '</span>
                </div>
                <div class="info-right">
                    <span class="info-label">ID Venta:</span> 
                    <span class="info-value">#' . htmlspecialchars($venta['id'] ?? 'N/A') . '</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-left">
                    <span class="info-label">Vendedor:</span> 
                    <span class="info-value">' . htmlspecialchars($venta['vendedor'] ?? 'N/A') . '</span>
                </div>
                <div class="info-right">
                    <span class="info-label">Método de Pago:</span> 
                    <span class="info-value">' . ucfirst($venta['metodo_pago'] ?? 'N/A') . '</span>
                </div>
            </div>
        </div>
    </div>';

// Check if we have products
if (!empty($detalles) && count($detalles) > 0) {
    $html .= '
    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Producto</th>
                <th style="width: 18%;">Precio Unit.</th>
                <th style="width: 12%;">Cant.</th>
                <th style="width: 20%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>';
    
    $subtotal_calculado = 0;
    $item_count = 0;
    
    foreach($detalles as $detalle) {
        $precio_unitario = floatval($detalle['precio_unitario'] ?? 0);
        $cantidad = intval($detalle['cantidad'] ?? 0);
        $item_total = $precio_unitario * $cantidad;
        $subtotal_calculado += $item_total;
        $item_count++;
        
        $producto_nombre = $detalle['producto_nombre'] ?? 'Producto ID ' . ($detalle['producto_id'] ?? 'N/A') . ' (eliminado)';
        
        $html .= '<tr>
            <td>' . htmlspecialchars($producto_nombre) . '</td>
            <td class="text-right">$' . number_format($precio_unitario, 2) . '</td>
            <td class="text-center">' . $cantidad . '</td>
            <td class="text-right">$' . number_format($item_total, 2) . '</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Show totals
    $total_venta = floatval($venta['total'] ?? 0);
    $diferencia = abs($subtotal_calculado - $total_venta);
    
    $html .= '
        <div class="total-section">
            <div class="total-row">Items vendidos: ' . $item_count . '</div>
            <div class="total-row">Subtotal calculado: $' . number_format($subtotal_calculado, 2) . '</div>';
    
    if ($diferencia > 0.01) {
        $html .= '<div class="total-row" style="color: #856404;">⚠️ Diferencia detectada: $' . number_format($diferencia, 2) . '</div>';
    }
    
    $html .= '
            <div class="grand-total">TOTAL: $' . number_format($total_venta, 2) . '</div>
        </div>';
} else {
    $html .= '
    <div class="no-products">
        <h3 style="margin-top: 0;">⚠️ Sin Productos Registrados</h3>
        <p>Esta venta no tiene productos registrados en el sistema.</p>
        <p><strong>Posibles causas:</strong></p>
        <ul style="text-align: left; display: inline-block;">
            <li>Error al procesar la venta en el POS</li>
            <li>Productos eliminados de la base de datos</li>
            <li>Problema en la inserción de venta_detalles</li>
        </ul>
    </div>
    
    <div class="total-section">
        <div class="grand-total">TOTAL: $' . number_format(floatval($venta['total'] ?? 0), 2) . '</div>
    </div>';
}

$html .= '
    <div class="footer">
        <p>Documento generado el ' . date('d/m/Y H:i:s') . '</p>
        <p>Sistema Bazar POS - Gracias por su compra</p>
    </div>
</body>
</html>';

// Si estamos en modo debug, mostrar el HTML directamente
if (isset($_GET['mode']) && $_GET['mode'] === 'html') {
    echo $html;
} else {
    // Retornar el HTML para uso en PDF
    return $html;
}
?>
