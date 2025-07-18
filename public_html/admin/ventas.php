<?php
// Load autoloader first for DomPDF and other dependencies
require_once '../../vendor/autoload.php';

// Import DomPDF classes at the top level
use Dompdf\Dompdf;
use Dompdf\Options;

// Handle AJAX request for export preview
if (isset($_GET['action']) && $_GET['action'] === 'preview_export') {
    require_once '../../includes/db.php';
    
    $format = $_GET['format'] ?? '';
    $valid_formats = ['csv', 'json', 'pdf'];
    
    if (!in_array($format, $valid_formats)) {
        header('HTTP/1.0 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Formato no válido']);
        exit;
    }
    
    // Get filter parameters for preview
    $search = $_GET['search'] ?? '';
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $usuario_filter = $_GET['usuario'] ?? '';
    $tipo_documento_filter = $_GET['tipo_documento'] ?? '';
    $metodo_pago_filter = $_GET['metodo_pago'] ?? '';
    
    // Build query with filters (same logic as export)
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(v.id = ? OR v.numero_documento LIKE ? OR u.nombre LIKE ?)";
        $params[] = $search;
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $where_conditions[] = "DATE(v.fecha) BETWEEN ? AND ?";
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin;
    }
    
    if (!empty($usuario_filter)) {
        $where_conditions[] = "v.usuario_id = ?";
        $params[] = $usuario_filter;
    }
    
    if (!empty($tipo_documento_filter)) {
        $where_conditions[] = "v.tipo_documento = ?";
        $params[] = $tipo_documento_filter;
    }
    
    if (!empty($metodo_pago_filter)) {
        $where_conditions[] = "v.metodo_pago = ?";
        $params[] = $metodo_pago_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get the data
    $sql = "SELECT v.id, v.fecha, v.total, v.metodo_pago, v.tipo_documento, v.numero_documento,
                   u.nombre as vendedor, u.correo as vendedor_email
            FROM ventas v 
            JOIN usuarios u ON v.usuario_id = u.id 
            $where_clause
            ORDER BY v.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $stats = [
        'total_ventas' => count($records),
        'total_ingresos' => array_sum(array_column($records, 'total')),
        'total_boletas' => count(array_filter($records, fn($r) => $r['tipo_documento'] === 'boleta')),
        'total_facturas' => count(array_filter($records, fn($r) => $r['tipo_documento'] === 'factura'))
    ];
    
    // Return preview data
    $response = [
        'format' => $format,
        'records' => $records,
        'stats' => $stats,
        'count' => count($records)
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle AJAX request for sale details FIRST - before any other processing
if (isset($_GET['action']) && $_GET['action'] === 'get_sale_details') {
    require_once '../../includes/db.php';
    
    $venta_id = (int)($_GET['venta_id'] ?? 0);
    
    if ($venta_id > 0) {
        // Get sale info first
        $ventaStmt = $conn->prepare("
            SELECT v.*, u.nombre as vendedor, u.correo as vendedor_email,
                   c.id as cliente_id, c.nombre as cliente_nombre, c.rut as cliente_rut,
                   c.rut_empresa as cliente_rut_empresa, c.razon_social as cliente_razon_social,
                   c.direccion as cliente_direccion, c.telefono as cliente_telefono,
                   c.correo as cliente_correo, c.tipo_cliente
            FROM ventas v 
            JOIN usuarios u ON v.usuario_id = u.id 
            LEFT JOIN clientes c ON v.cliente_id = c.id
            WHERE v.id = ?
        ");
        $ventaStmt->execute([$venta_id]);
        $venta = $ventaStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$venta) {
            header('HTTP/1.0 404 Not Found');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Venta no encontrada']);
            exit;
        }
        
        // Get sale details (products)
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
        
        // Return both sale info and details
        $response = [
            'venta' => $venta,
            'productos' => $detalles
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de venta inválido']);
    exit;
}

// Handle exports first, before any output
require_once '../../includes/db.php';
require_once '../../includes/funciones.php'; // CSRF helpers

// Check for single sale PDF export first
$export_venta = $_GET['export_venta'] ?? '';
if ($export_venta) {
    // Get sale details with products
    $venta_id = (int)$export_venta;
    
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
        header('HTTP/1.0 404 Not Found');
        exit('Venta no encontrada');
    }
    
    // Get sale details (products)
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
    
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('debugKeepTemp', true);
    $options->set('debugCss', true);
    $dompdf = new Dompdf($options);
    
    // Debug: Log the data we're working with
    error_log("PDF Export - Venta data: " . print_r($venta, true));
    error_log("PDF Export - Detalles count: " . count($detalles));
    error_log("PDF Export - Detalles data: " . print_r($detalles, true));
    
    // Use the PDF template
    ob_start();
    include 'pdf_template.php';
    $html = ob_get_clean();
    
    // Debug: Log the generated HTML
    error_log("PDF Export - Generated HTML length: " . strlen($html));
    error_log("PDF Export - HTML preview: " . substr($html, 0, 500) . "...");
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $filename = "boleta_{$venta['numero_documento']}_" . date('d_m_Y', strtotime($venta['fecha']));
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"{$filename}.pdf\"");
    echo $dompdf->output();
    exit;
}

// Check for export request first
$export = $_GET['export'] ?? '';
if ($export) {
    // Get filter parameters for export
    $search = $_GET['search'] ?? '';
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    $usuario_filter = $_GET['usuario'] ?? '';
    $tipo_documento_filter = $_GET['tipo_documento'] ?? '';
    $metodo_pago_filter = $_GET['metodo_pago'] ?? '';
    
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(v.id = ? OR v.numero_documento LIKE ? OR u.nombre LIKE ?)";
        $params[] = $search;
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $where_conditions[] = "DATE(v.fecha) BETWEEN ? AND ?";
        $params[] = $fecha_inicio;
        $params[] = $fecha_fin;
    }
    
    if (!empty($usuario_filter)) {
        $where_conditions[] = "v.usuario_id = ?";
        $params[] = $usuario_filter;
    }
    
    if (!empty($tipo_documento_filter)) {
        $where_conditions[] = "v.tipo_documento = ?";
        $params[] = $tipo_documento_filter;
    }
    
    if (!empty($metodo_pago_filter)) {
        $where_conditions[] = "v.metodo_pago = ?";
        $params[] = $metodo_pago_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $sql = "SELECT v.id, v.fecha, v.total, v.metodo_pago, v.tipo_documento, v.numero_documento,
                   u.nombre as vendedor, u.correo as vendedor_email
            FROM ventas v 
            JOIN usuarios u ON v.usuario_id = u.id 
            $where_clause
            ORDER BY v.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $ventasData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate filename with timestamp
    $timestamp = date('d_m_Y_H_i');
    $filename = "registro_ventas_{$timestamp}";
    
    if ($export === 'json') {
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
        echo json_encode($ventasData, JSON_PRETTY_PRINT);
        exit;
    }
    
    if ($export === 'csv') {
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        $out = fopen('php://output','w');
        // CSV headers
        fputcsv($out, ['ID', 'Fecha', 'Vendedor', 'Email Vendedor', 'Total', 'Método Pago', 'Tipo Documento', 'Número Documento']);
        foreach($ventasData as $row) {
            fputcsv($out, [
                $row['id'],
                $row['fecha'],
                $row['vendedor'],
                $row['vendedor_email'],
                $row['total'],
                $row['metodo_pago'],
                $row['tipo_documento'],
                $row['numero_documento']
            ]);
        }
        fclose($out);
        exit;
    }
    
    if ($export === 'pdf') {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        
        // Generate PDF content
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .header { text-align: center; margin-bottom: 30px; }
                .title { font-size: 18px; font-weight: bold; color: #333; }
                .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .total-row { background-color: #f9f9f9; font-weight: bold; }
                .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">REGISTRO DE VENTAS</div>
                <div class="subtitle">Sistema Bazar - ' . date('d/m/Y H:i') . '</div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Vendedor</th>
                        <th>Total</th>
                        <th>Método</th>
                        <th>Tipo Doc.</th>
                        <th>N° Documento</th>
                    </tr>
                </thead>
                <tbody>';
        
        $total_general = 0;
        foreach($ventasData as $row) {
            $total_general += $row['total'];
            $html .= '<tr>
                <td>' . $row['id'] . '</td>
                <td>' . date('d/m/Y H:i', strtotime($row['fecha'])) . '</td>
                <td>' . htmlspecialchars($row['vendedor']) . '</td>
                <td>$' . number_format($row['total'], 2) . '</td>
                <td>' . ucfirst($row['metodo_pago']) . '</td>
                <td>' . ucfirst($row['tipo_documento']) . '</td>
                <td>' . htmlspecialchars($row['numero_documento']) . '</td>
            </tr>';
        }
        
        $html .= '<tr class="total-row">
                    <td colspan="3"><strong>TOTAL GENERAL</strong></td>
                    <td><strong>$' . number_format($total_general, 2) . '</strong></td>
                    <td colspan="3"><strong>' . count($ventasData) . ' ventas</strong></td>
                </tr>';
        
        $html .= '</tbody></table>
            <div class="footer">
                Generado el ' . date('d/m/Y H:i:s') . ' - Sistema Bazar POS
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"{$filename}.pdf\"");
        echo $dompdf->output();
        exit;
    }
}

// Now include layout and continue with normal page logic
require_once '../../includes/layout_admin.php';

// CSRF validation for POST if needed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    verify_csrf_token($_POST['csrf_token'] ?? '');
    // You can add POST actions here (e.g., delete)
}

// Get filter and search parameters
$search = $_GET['search'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$usuario_filter = $_GET['usuario'] ?? '';
$tipo_documento_filter = $_GET['tipo_documento'] ?? '';
$metodo_pago_filter = $_GET['metodo_pago'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'fecha';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Valid sort columns
$valid_sorts = ['id', 'fecha', 'total', 'metodo_pago', 'tipo_documento', 'vendedor'];
if (!in_array($sort_by, $valid_sorts)) {
    $sort_by = 'fecha';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Build WHERE conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(v.id = ? OR v.numero_documento LIKE ? OR u.nombre LIKE ?)";
    $params[] = $search;
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where_conditions[] = "DATE(v.fecha) BETWEEN ? AND ?";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
}

if (!empty($usuario_filter)) {
    $where_conditions[] = "v.usuario_id = ?";
    $params[] = $usuario_filter;
}

if (!empty($tipo_documento_filter)) {
    $where_conditions[] = "v.tipo_documento = ?";
    $params[] = $tipo_documento_filter;
}

if (!empty($metodo_pago_filter)) {
    $where_conditions[] = "v.metodo_pago = ?";
    $params[] = $metodo_pago_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Pagination parameters
$page = (int)($_GET['page'] ?? 1);
$limit = 15;
$offset = ($page - 1) * $limit;

// Count total sales with filters
$countSql = "SELECT COUNT(*) FROM ventas v JOIN usuarios u ON v.usuario_id = u.id $where_clause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalSales = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalSales / $limit);

// Fetch sales records with all details
$sql = "SELECT v.id, v.fecha, v.total, v.metodo_pago, v.tipo_documento, v.numero_documento,
               u.nombre as vendedor, u.correo as vendedor_email
        FROM ventas v 
        JOIN usuarios u ON v.usuario_id = u.id 
        $where_clause
        ORDER BY " . ($sort_by === 'vendedor' ? 'u.nombre' : "v.$sort_by") . " $sort_order
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

// Bind regular parameters first
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}

// Bind LIMIT and OFFSET as integers
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

$stmt->execute();
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get users for filter dropdown
$usersStmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE rol IN ('vendedor', 'jefe', 'admin') ORDER BY nombre");
$usersStmt->execute();
$usuarios = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total_ventas,
    SUM(total) as total_ingresos,
    AVG(total) as promedio_venta,
    COUNT(CASE WHEN tipo_documento = 'boleta' THEN 1 END) as total_boletas,
    COUNT(CASE WHEN tipo_documento = 'factura' THEN 1 END) as total_facturas
    FROM ventas v 
    JOIN usuarios u ON v.usuario_id = u.id 
    $where_clause");
$statsStmt->execute($params);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
.ventas-container {
    padding: 0;
}

.ventas-header {
    background: linear-gradient(135deg, rgba(45, 27, 105, 0.1), rgba(17, 153, 142, 0.1));
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.ventas-title {
    color: #ffffff;
    font-size: 2.2rem;
    font-weight: 300;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #ff6b6b, #feca57, #48dbfb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 255, 255, 0.2);
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.filters-section {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(15px);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.filter-input, .filter-select {
    width: 100%;
    background: rgba(30, 30, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    color: white;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #48dbfb;
    box-shadow: 0 0 0 2px rgba(72, 219, 251, 0.3);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn {
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #48dbfb, #0abde3);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #00ff7f, #7bed9f);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #feca57, #ffd93d);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.table-container {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(15px);
    overflow-x: auto;
}

.ventas-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.ventas-table th, .ventas-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.ventas-table th {
    background: rgba(45, 27, 105, 0.3);
    color: #ffffff;
    font-weight: 600;
    position: sticky;
    top: 0;
    cursor: pointer;
    transition: all 0.3s ease;
}

.ventas-table th:hover {
    background: rgba(45, 27, 105, 0.5);
}

.ventas-table td {
    color: rgba(255, 255, 255, 0.9);
}

.ventas-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.sort-icon {
    margin-left: 0.5rem;
    font-size: 0.8rem;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    background: rgba(30, 30, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.page-link:hover, .page-link.active {
    background: #48dbfb;
    border-color: #48dbfb;
    color: white;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-boleta {
    background: rgba(72, 219, 251, 0.2);
    color: #48dbfb;
    border: 1px solid rgba(72, 219, 251, 0.3);
}

.badge-factura {
    background: rgba(254, 202, 87, 0.2);
    color: #feca57;
    border: 1px solid rgba(254, 202, 87, 0.3);
}

.badge-efectivo {
    background: rgba(0, 255, 127, 0.2);
    color: #00ff7f;
    border: 1px solid rgba(0, 255, 127, 0.3);
}

.badge-tarjeta {
    background: rgba(255, 107, 107, 0.2);
    color: #ff6b6b;
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.badge-transferencia {
    background: rgba(128, 90, 213, 0.2);
    color: #805ad5;
    border: 1px solid rgba(128, 90, 213, 0.3);
}

.btn-action {
    background: rgba(30, 30, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn-view:hover {
    background: #48dbfb;
    border-color: #48dbfb;
    color: white;
}

.btn-pdf:hover {
    background: #ff6b6b;
    border-color: #ff6b6b;
    color: white;
}

.document-link:hover {
    color: #feca57 !important;
    text-decoration: underline !important;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.95), rgba(45, 27, 105, 0.4));
    margin: 2% auto;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    backdrop-filter: blur(20px);
    animation: slideDown 0.3s ease;
}

.modal-header {
    background: linear-gradient(135deg, #48dbfb, #0abde3);
    color: white;
    padding: 1.5rem;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

.modal-body {
    padding: 2rem;
    color: white;
}

.sale-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

.info-value {
    font-size: 1rem;
    color: white;
    font-weight: 600;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 8px;
    overflow: hidden;
}

.products-table th,
.products-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.products-table th {
    background: rgba(72, 219, 251, 0.2);
    color: white;
    font-weight: 600;
}

.products-table td {
    color: rgba(255, 255, 255, 0.9);
}

.products-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.total-summary {
    margin-top: 1.5rem;
    padding: 1rem;
    background: rgba(72, 219, 251, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(72, 219, 251, 0.3);
    text-align: right;
}

.total-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #48dbfb;
}

.loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 3rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-top: 4px solid #48dbfb;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Preview Modal Styles */
.preview-modal {
    display: none;
    position: fixed;
    z-index: 10001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(8px);
    animation: fadeIn 0.3s ease;
}

.preview-modal-content {
    background: linear-gradient(135deg, rgba(20, 20, 30, 0.98), rgba(35, 25, 85, 0.9));
    margin: 1% auto;
    padding: 0;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    width: 95%;
    max-width: 1200px;
    max-height: 95vh;
    overflow: hidden;
    backdrop-filter: blur(25px);
    animation: slideDown 0.4s ease;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.preview-modal-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.preview-modal-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.preview-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.preview-download-btn {
    background: linear-gradient(135deg, #00c851, #00ff7f);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.preview-download-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 200, 81, 0.3);
    background: linear-gradient(135deg, #00a041, #00e66f);
}

.preview-close {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    font-size: 1.8rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.preview-close:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: rotate(90deg);
}

.preview-modal-body {
    padding: 2rem;
    color: white;
    max-height: calc(95vh - 100px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
}

.preview-modal-body::-webkit-scrollbar {
    width: 8px;
}

.preview-modal-body::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.preview-modal-body::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.preview-modal-body::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* CSV Preview Styles */
.csv-preview-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.csv-preview-table th,
.csv-preview-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 0.9rem;
    vertical-align: top;
}

.csv-preview-table th {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.5), rgba(118, 75, 162, 0.5));
    color: white;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.8rem;
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.csv-preview-table td {
    color: rgba(255, 255, 255, 0.9);
    border-right: 1px solid rgba(255, 255, 255, 0.05);
}

.csv-preview-table td:last-child {
    border-right: none;
}

.csv-preview-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.csv-preview-table tbody tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.02);
}

.csv-preview-table tbody tr:nth-child(even):hover {
    background: rgba(255, 255, 255, 0.07);
}

/* JSON Preview Styles */
.json-preview {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 1.5rem;
    font-family: 'Courier New', Consolas, monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    color: #f8f8f2;
    overflow-x: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.json-preview .json-key {
    color: #66d9ff;
    font-weight: 600;
}

.json-preview .json-string {
    color: #a6e22e;
}

.json-preview .json-number {
    color: #fd971f;
}

.json-preview .json-boolean {
    color: #ae81ff;
}

.json-preview .json-null {
    color: #f92672;
}

/* PDF Preview Styles */
.pdf-preview-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 2rem;
    margin-top: 1rem;
    color: #333;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.pdf-preview-container .header {
    text-align: center;
    margin-bottom: 2rem;
    border-bottom: 2px solid #667eea;
    padding-bottom: 1rem;
}

.pdf-preview-container .title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.pdf-preview-container .subtitle {
    font-size: 1.1rem;
    color: #666;
}

.pdf-preview-container table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.pdf-preview-container th,
.pdf-preview-container td {
    border: 1px solid #ddd;
    padding: 0.75rem;
    text-align: left;
}

.pdf-preview-container th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 600;
}

.pdf-preview-container .total-row {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    font-weight: bold;
    color: #667eea;
}

.pdf-preview-container .footer {
    margin-top: 2rem;
    text-align: center;
    font-size: 0.9rem;
    color: #666;
    border-top: 1px solid #ddd;
    padding-top: 1rem;
}

/* Loading Animation for Preview */
.preview-loading {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 4rem;
    color: rgba(255, 255, 255, 0.8);
}

.preview-loading .spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(255, 255, 255, 0.1);
    border-top: 5px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

.preview-info {
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    color: rgba(255, 255, 255, 0.9);
}

.preview-info-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #667eea;
}

.stats-preview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stats-preview-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.stats-preview-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.stats-preview-label {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.7);
}
</style>

<div class="ventas-container">
    <!-- Header with Statistics -->
    <div class="ventas-header">
        <h1 class="ventas-title">Registro de Ventas Detallado</h1>
        <p style="color: rgba(255, 255, 255, 0.7); margin: 0;">
            Sistema completo de consulta y exportación de ventas
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['total_ventas']) ?></div>
            <div class="stat-label">Total Ventas</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($stats['total_ingresos'], 2) ?></div>
            <div class="stat-label">Ingresos Totales</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?= number_format($stats['promedio_venta'], 2) ?></div>
            <div class="stat-label">Venta Promedio</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['total_boletas']) ?></div>
            <div class="stat-label">Boletas</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['total_facturas']) ?></div>
            <div class="stat-label">Facturas</div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
        <h3 style="color: #ffffff; margin-bottom: 1.5rem;">
            <i class="bi bi-filter"></i> Filtros y Búsqueda
        </h3>
        
        <form method="GET" class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Búsqueda General</label>
                <input type="text" name="search" class="filter-input" 
                       placeholder="ID, número documento o vendedor..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="filter-input" 
                       value="<?= htmlspecialchars($fecha_inicio) ?>">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="filter-input" 
                       value="<?= htmlspecialchars($fecha_fin) ?>">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Vendedor</label>
                <select name="usuario" class="filter-select">
                    <option value="">Todos los vendedores</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= $usuario['id'] ?>" <?= $usuario_filter == $usuario['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($usuario['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Tipo Documento</label>
                <select name="tipo_documento" class="filter-select">
                    <option value="">Todos</option>
                    <option value="boleta" <?= $tipo_documento_filter === 'boleta' ? 'selected' : '' ?>>Boleta</option>
                    <option value="factura" <?= $tipo_documento_filter === 'factura' ? 'selected' : '' ?>>Factura</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Método de Pago</label>
                <select name="metodo_pago" class="filter-select">
                    <option value="">Todos</option>
                    <option value="efectivo" <?= $metodo_pago_filter === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                    <option value="tarjeta" <?= $metodo_pago_filter === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                    <option value="transferencia" <?= $metodo_pago_filter === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                </select>
            </div>
            
            <!-- Hidden sort parameters -->
            <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
            <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sort_order) ?>">
        </form>
        
        <div class="action-buttons">
            <button type="submit" form="filter-form" class="btn btn-primary">
                <i class="bi bi-search"></i> Buscar
            </button>
            <button onclick="previewExport('csv')" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> Exportar CSV
            </button>
            <button onclick="previewExport('json')" class="btn btn-warning">
                <i class="bi bi-file-earmark-code"></i> Exportar JSON
            </button>
            <button onclick="previewExport('pdf')" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
            </button>
            <a href="?" class="btn" style="background: rgba(255,255,255,0.1); color: white;">
                <i class="bi bi-arrow-clockwise"></i> Limpiar Filtros
            </a>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="table-container">
        <h3 style="color: #ffffff; margin-bottom: 1rem;">
            <i class="bi bi-table"></i> 
            Resultados (<?= number_format($totalSales) ?> ventas encontradas)
        </h3>
        
        <table class="ventas-table">
            <thead>
                <tr>
                    <th onclick="sortTable('id')">
                        ID 
                        <span class="sort-icon">
                            <?= $sort_by === 'id' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('fecha')">
                        Fecha
                        <span class="sort-icon">
                            <?= $sort_by === 'fecha' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('vendedor')">
                        Vendedor
                        <span class="sort-icon">
                            <?= $sort_by === 'vendedor' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('total')">
                        Total
                        <span class="sort-icon">
                            <?= $sort_by === 'total' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('metodo_pago')">
                        Método Pago
                        <span class="sort-icon">
                            <?= $sort_by === 'metodo_pago' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('tipo_documento')">
                        Tipo Documento
                        <span class="sort-icon">
                            <?= $sort_by === 'tipo_documento' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th>N° Documento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ventas)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem; color: rgba(255,255,255,0.5);">
                        <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        No se encontraron ventas con los filtros aplicados
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td><strong>#<?= htmlspecialchars($venta['id']) ?></strong></td>
                        <td><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td>
                        <td>
                            <div><?= htmlspecialchars($venta['vendedor']) ?></div>
                            <small style="color: rgba(255,255,255,0.5);"><?= htmlspecialchars($venta['vendedor_email']) ?></small>
                        </td>
                        <td><strong>$<?= number_format($venta['total'], 2) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $venta['metodo_pago'] ?>">
                                <?= ucfirst($venta['metodo_pago']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $venta['tipo_documento'] ?>">
                                <?= ucfirst($venta['tipo_documento']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="#" class="document-link" onclick="showSaleDetails(<?= $venta['id'] ?>)" 
                               style="color: #48dbfb; text-decoration: none; font-weight: bold;">
                                <code><?= htmlspecialchars($venta['numero_documento']) ?></code>
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem;">
                                <button onclick="showSaleDetails(<?= $venta['id'] ?>)" 
                                        class="btn-action btn-view" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="?export_venta=<?= $venta['id'] ?>" 
                                   class="btn-action btn-pdf" title="Exportar PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="page-link <?= $i == $page ? 'active' : '' ?>" 
                   href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para detalles de venta -->
<div id="saleDetailsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="bi bi-receipt"></i>
                Detalles de la Venta
            </h3>
            <button class="modal-close" onclick="closeSaleModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="saleDetailsContent">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para previsualización de exportaciones -->
<div id="previewModal" class="preview-modal">
    <div class="preview-modal-content">
        <div class="preview-modal-header">
            <h3 class="preview-modal-title" id="previewTitle">
                <i class="bi bi-eye"></i>
                Previsualización de Exportación
            </h3>
            <div class="preview-controls">
                <a href="#" id="previewDownloadBtn" class="preview-download-btn" style="display: none;">
                    <i class="bi bi-download"></i>
                    Descargar
                </a>
                <button class="preview-close" onclick="closePreviewModal()">&times;</button>
            </div>
        </div>
        <div class="preview-modal-body">
            <div id="previewContent">
                <div class="preview-loading">
                    <div class="spinner"></div>
                    <p>Generando previsualización...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form reference fix
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.filters-grid');
    form.id = 'filter-form';
    
    // Auto-submit on filter change
    const filterInputs = document.querySelectorAll('.filter-input, .filter-select');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.type !== 'text') {
                form.submit();
            }
        });
        
        if (input.type === 'text') {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    form.submit();
                }, 500);
            });
        }
    });
});

function sortTable(column) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort_by');
    const currentOrder = urlParams.get('sort_order');
    
    let newOrder = 'DESC';
    if (currentSort === column && currentOrder === 'DESC') {
        newOrder = 'ASC';
    }
    
    urlParams.set('sort_by', column);
    urlParams.set('sort_order', newOrder);
    urlParams.delete('page'); // Reset to first page when sorting
    
    window.location.search = urlParams.toString();
}

// Función para navegación de páginas
function goToPage(page) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    
    window.location.search = urlParams.toString();
}

// Inicialización mejorada
document.addEventListener('DOMContentLoaded', function() {
    // Manejar formularios de filtro
    const filterForms = document.querySelectorAll('form[method="GET"]');
    filterForms.forEach(form => {
        // Auto-submit en cambios de select
        const selects = form.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                form.submit();
            });
        });
    });
    
    // Manejar enlaces de paginación
    const pageLinks = document.querySelectorAll('.page-btn');
    pageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = new URL(this.href);
            const page = url.searchParams.get('page') || 1;
            
            goToPage(page);
        });
    });
    
    // Manejar enlaces de exportación
    const exportLinks = document.querySelectorAll('a[href*="export"]');
    exportLinks.forEach(link => {
        link.addEventListener('click', function() {
            // No guardar posición para exportaciones, ya que no recarga la página
        });
    });
});

// Guardar posición antes de salir de la página
window.addEventListener('beforeunload', function() {
    if (performance.navigation.type !== 1) {
        saveScrollPosition();
    }
});

// Función para guardar la posición de scroll
function saveScrollPosition() {
    sessionStorage.setItem('ventas_scroll_position', window.scrollY);
}

// Función para restaurar la posición de scroll
function restoreScrollPosition() {
    const scrollPosition = sessionStorage.getItem('ventas_scroll_position');
    if (scrollPosition) {
        window.scrollTo(0, parseInt(scrollPosition));
        sessionStorage.removeItem('ventas_scroll_position');
    }
}

// Restaurar posición al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    restoreScrollPosition();
});

// Funciones para el modal de detalles de venta
function showSaleDetails(ventaId) {
    const modal = document.getElementById('saleDetailsModal');
    const content = document.getElementById('saleDetailsContent');
    
    // Mostrar modal y loading
    modal.style.display = 'block';
    content.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p style="margin-top: 1rem; text-align: center; color: rgba(255,255,255,0.7);">
                Cargando detalles de la venta...
            </p>
        </div>
    `;
    
    // Obtener datos de la venta
    fetch(`?action=get_sale_details&venta_id=${ventaId}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers.get('content-type'));
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: Error del servidor`);
            }
            
            // Verificar que la respuesta sea JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // Si no es JSON, obtener el texto para debug
                return response.text().then(text => {
                    console.error('Response text:', text);
                    throw new Error('La respuesta no es JSON válido. Recibido: ' + text.substring(0, 200));
                });
            }
            
            return response.json();
        })
        .then(data => {
            displaySaleDetails(ventaId, data);
        })
        .catch(error => {
            console.error('Error details:', error);
            console.error('Stack:', error.stack);
            content.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #ff6b6b;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                    <h4>Error al cargar los detalles</h4>
                    <p><strong>Error:</strong> ${error.message}</p>
                    <p><strong>Venta ID:</strong> ${ventaId}</p>
                    <details style="margin-top: 1rem; text-align: left; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px;">
                        <summary style="cursor: pointer; color: #feca57;">Ver detalles técnicos</summary>
                        <pre style="margin-top: 0.5rem; font-size: 0.8rem;">${error.stack || 'No stack trace available'}</pre>
                    </details>
                    <button onclick="closeSaleModal()" class="btn btn-primary" style="margin-top: 1rem;">
                        Cerrar
                    </button>
                    <button onclick="showSaleDetails(${ventaId})" class="btn btn-warning" style="margin-top: 1rem; margin-left: 1rem;">
                        Reintentar
                    </button>
                </div>
            `;
        });
}

function displaySaleDetails(ventaId, data) {
    const content = document.getElementById('saleDetailsContent');
    
    console.log('Displaying sale details for ID:', ventaId);
    console.log('Data received:', data);
    
    // Extract sale info and products from response
    const saleInfo = data.venta || {};
    const products = data.productos || [];
    
    console.log('Products count:', products.length);
    console.log('Products data:', products);
    
    // Use the data from the response instead of trying to parse from table
    const venta = {
        id: saleInfo.id || ventaId,
        fecha: saleInfo.fecha ? new Date(saleInfo.fecha).toLocaleString('es-ES') : 'N/A',
        vendedor: saleInfo.vendedor || 'N/A',
        total: saleInfo.total ? `$${parseFloat(saleInfo.total).toFixed(2)}` : 'N/A',
        metodo_pago: saleInfo.metodo_pago ? saleInfo.metodo_pago.charAt(0).toUpperCase() + saleInfo.metodo_pago.slice(1) : 'N/A',
        tipo_documento: saleInfo.tipo_documento ? saleInfo.tipo_documento.charAt(0).toUpperCase() + saleInfo.tipo_documento.slice(1) : 'N/A',
        numero_documento: saleInfo.numero_documento || 'N/A'
    };
    
    // Información del cliente
    const cliente = {
        id: saleInfo.cliente_id || null,
        nombre: saleInfo.cliente_nombre || 'No asignado',
        rut: saleInfo.cliente_rut || 'N/A',
        rut_empresa: saleInfo.cliente_rut_empresa || 'N/A',
        razon_social: saleInfo.cliente_razon_social || 'N/A',
        direccion: saleInfo.cliente_direccion || 'N/A',
        telefono: saleInfo.cliente_telefono || 'N/A',
        correo: saleInfo.cliente_correo || 'N/A',
        tipo_cliente: saleInfo.tipo_cliente || 'persona'
    };
    
    // Calcular totales
    let subtotal = 0;
    products.forEach(product => {
        const precio = parseFloat(product.precio_unitario || 0);
        const cantidad = parseInt(product.cantidad || 0);
        subtotal += precio * cantidad;
        console.log(`Product: ${product.producto_nombre}, Price: ${precio}, Qty: ${cantidad}, Subtotal: ${precio * cantidad}`);
    });
    
    console.log('Calculated subtotal:', subtotal);
    
    const html = `
        <div class="sale-info-grid">
            <div class="info-item">
                <span class="info-label">ID de Venta</span>
                <span class="info-value">#${venta.id}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha</span>
                <span class="info-value">${venta.fecha}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Vendedor</span>
                <span class="info-value">${venta.vendedor}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tipo Documento</span>
                <span class="info-value">${venta.tipo_documento}</span>
            </div>
            <div class="info-item">
                <span class="info-label">N° Documento</span>
                <span class="info-value">${venta.numero_documento}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Método de Pago</span>
                <span class="info-value">${venta.metodo_pago}</span>
            </div>
        </div>
        
        <h4 style="color: white; margin-bottom: 1rem; margin-top: 2rem;">
            <i class="bi bi-person"></i>
            Información del Cliente
        </h4>
        
        <div class="sale-info-grid">
            ${cliente.id ? `
                <div class="info-item">
                    <span class="info-label">Cliente</span>
                    <span class="info-value">${cliente.nombre}</span>
                </div>
                ${cliente.tipo_cliente === 'empresa' ? `
                    <div class="info-item">
                        <span class="info-label">RUT Empresa</span>
                        <span class="info-value">${cliente.rut_empresa}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Razón Social</span>
                        <span class="info-value">${cliente.razon_social}</span>
                    </div>
                ` : `
                    <div class="info-item">
                        <span class="info-label">RUT</span>
                        <span class="info-value">${cliente.rut}</span>
                    </div>
                `}
                <div class="info-item">
                    <span class="info-label">Dirección</span>
                    <span class="info-value">${cliente.direccion}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">${cliente.telefono}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Correo</span>
                    <span class="info-value">${cliente.correo}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tipo Cliente</span>
                    <span class="info-value">
                        <span style="background: ${cliente.tipo_cliente === 'empresa' ? '#007bff' : '#6c757d'}; 
                                     color: white; 
                                     padding: 0.25rem 0.5rem; 
                                     border-radius: 0.25rem; 
                                     font-size: 0.875rem;">
                            ${cliente.tipo_cliente.charAt(0).toUpperCase() + cliente.tipo_cliente.slice(1)}
                        </span>
                    </span>
                </div>
            ` : `
                <div class="info-item">
                    <span class="info-label">Cliente</span>
                    <span class="info-value" style="color: rgba(255,255,255,0.5); font-style: italic;">No asignado</span>
                </div>
                <div style="text-align: center; padding: 1rem; color: rgba(255,255,255,0.5); grid-column: 1 / -1;">
                    <i class="bi bi-person-dash" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                    <p>Esta venta no tiene un cliente asociado.</p>
                </div>
            `}
        </div>
        
        <h4 style="color: white; margin-bottom: 1rem; margin-top: 2rem;">
            <i class="bi bi-box-seam"></i>
            Productos Vendidos (${products.length} items)
        </h4>
        
        ${products.length > 0 ? `
        <table class="products-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio Unitario</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                ${products.map(product => {
                    const precio = parseFloat(product.precio_unitario || 0);
                    const cantidad = parseInt(product.cantidad || 0);
                    const itemTotal = precio * cantidad;
                    const nombreProducto = product.producto_nombre || `Producto ID ${product.producto_id} (eliminado)`;
                    
                    return `
                        <tr>
                            <td><strong>${nombreProducto}</strong></td>
                            <td>$${precio.toFixed(2)}</td>
                            <td>${cantidad}</td>
                            <td><strong>$${itemTotal.toFixed(2)}</strong></td>
                        </tr>
                    `;
                }).join('')}
            </tbody>
        </table>
        ` : `
        <div style="text-align: center; padding: 2rem; color: rgba(255,255,255,0.5);">
            <i class="bi bi-box" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
            <p>No se encontraron productos para esta venta</p>
            <p><small>Esto puede indicar que:</small></p>
            <ul style="text-align: left; display: inline-block;">
                <li>No hay registros en la tabla venta_detalles para esta venta</li>
                <li>Los productos fueron eliminados de la base de datos</li>
                <li>Hay un problema con la relación entre las tablas</li>
            </ul>
            <p><a href="debug_venta_detalles.php?venta_id=${ventaId}" target="_blank" style="color: #48dbfb;">Ver información de debug</a></p>
        </div>
        `}
        
        <div class="total-summary">
            <div style="margin-bottom: 0.5rem;">
                <strong>Subtotal calculado: $${subtotal.toFixed(2)}</strong>
            </div>
            <div class="total-amount">
                TOTAL: ${venta.total}
            </div>
            ${Math.abs(subtotal - parseFloat(saleInfo.total || 0)) > 0.01 ? 
                `<div style="color: #feca57; margin-top: 0.5rem; font-size: 0.9rem;">
                    ⚠️ Diferencia detectada entre subtotal calculado y total registrado
                </div>` : ''}
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
            <a href="?export_venta=${ventaId}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i>
                Descargar PDF
            </a>
            <button onclick="closeSaleModal()" class="btn btn-primary">
                <i class="bi bi-x-circle"></i>
                Cerrar
            </button>
        </div>
    `;
    
    content.innerHTML = html;
}

function closeSaleModal() {
    const modal = document.getElementById('saleDetailsModal');
    modal.style.display = 'none';
}

// Cerrar modal al hacer clic fuera de él
window.addEventListener('click', function(event) {
    const modal = document.getElementById('saleDetailsModal');
    if (event.target === modal) {
        closeSaleModal();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeSaleModal();
        closePreviewModal();
    }
});

// Funciones para previsualización de exportaciones
function previewExport(format) {
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    const title = document.getElementById('previewTitle');
    const downloadBtn = document.getElementById('previewDownloadBtn');
    
    // Configurar título según el formato
    const formats = {
        'csv': { icon: 'bi-file-earmark-spreadsheet', name: 'CSV' },
        'json': { icon: 'bi-file-earmark-code', name: 'JSON' },
        'pdf': { icon: 'bi-file-earmark-pdf', name: 'PDF' }
    };
    
    title.innerHTML = `<i class="bi ${formats[format].icon}"></i> Previsualización ${formats[format].name}`;
    
    // Mostrar modal con loading
    modal.style.display = 'block';
    content.innerHTML = `
        <div class="preview-loading">
            <div class="spinner"></div>
            <p>Generando previsualización ${formats[format].name}...</p>
        </div>
    `;
    downloadBtn.style.display = 'none';
    
    // Obtener parámetros de filtro actuales
    const urlParams = new URLSearchParams(window.location.search);
    const params = {
        search: urlParams.get('search') || '',
        fecha_inicio: urlParams.get('fecha_inicio') || '',
        fecha_fin: urlParams.get('fecha_fin') || '',
        usuario: urlParams.get('usuario') || '',
        tipo_documento: urlParams.get('tipo_documento') || '',
        metodo_pago: urlParams.get('metodo_pago') || ''
    };
    
    // Construir URL de previsualización
    const previewParams = new URLSearchParams({
        action: 'preview_export',
        format: format,
        ...Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== ''))
    });
    
    // Construir URL de descarga
    const downloadParams = new URLSearchParams({
        export: format,
        ...Object.fromEntries(Object.entries(params).filter(([_, v]) => v !== ''))
    });
    
    // Hacer petición para previsualización
    fetch(`?${previewParams}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: Error del servidor`);
            }
            return response.json();
        })
        .then(data => {
            displayPreview(format, data);
            // Configurar botón de descarga
            downloadBtn.href = `?${downloadParams}`;
            downloadBtn.style.display = 'inline-flex';
        })
        .catch(error => {
            console.error('Error en previsualización:', error);
            content.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: #ff6b6b;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                    <h4>Error al generar la previsualización</h4>
                    <p><strong>Error:</strong> ${error.message}</p>
                    <button onclick="closePreviewModal()" class="btn btn-primary" style="margin-top: 1rem;">
                        Cerrar
                    </button>
                </div>
            `;
        });
}

function displayPreview(format, data) {
    const content = document.getElementById('previewContent');
    
    switch (format) {
        case 'csv':
            displayCSVPreview(content, data);
            break;
        case 'json':
            displayJSONPreview(content, data);
            break;
        case 'pdf':
            displayPDFPreview(content, data);
            break;
    }
}

function displayCSVPreview(container, data) {
    const stats = data.stats || {};
    const records = data.records || [];
    
    // Verificar que tenemos datos válidos
    if (records.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: rgba(255,255,255,0.7);">
                <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                <h4>No hay datos para exportar</h4>
                <p>No se encontraron registros con los filtros aplicados.</p>
            </div>
        `;
        return;
    }
    
    const html = `
        <div class="preview-info">
            <div class="preview-info-title">📊 Información del Archivo CSV</div>
            <p>Se generará un archivo CSV con <strong>${records.length} registros</strong> de ventas.</p>
            <p>El archivo incluirá todas las columnas principales: ID, Fecha, Vendedor, Email, Total, Método de Pago, Tipo de Documento y Número de Documento.</p>
        </div>
        
        <div class="stats-preview">
            <div class="stats-preview-item">
                <div class="stats-preview-value">${records.length}</div>
                <div class="stats-preview-label">Total Registros</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">$${parseFloat(stats.total_ingresos || 0).toLocaleString('es-ES', {minimumFractionDigits: 2})}</div>
                <div class="stats-preview-label">Ingresos Totales</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">${stats.total_boletas || 0}</div>
                <div class="stats-preview-label">Boletas</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">${stats.total_facturas || 0}</div>
                <div class="stats-preview-label">Facturas</div>
            </div>
        </div>
        
        <h4 style="color: white; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-table"></i>
            Vista Previa de Datos (${Math.min(10, records.length)} de ${records.length} registros)
        </h4>
        
        <div style="overflow-x: auto; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.1);">
            <table class="csv-preview-table">
                <thead>
                    <tr>
                        <th style="min-width: 60px;">ID</th>
                        <th style="min-width: 140px;">Fecha</th>
                        <th style="min-width: 120px;">Vendedor</th>
                        <th style="min-width: 160px;">Email Vendedor</th>
                        <th style="min-width: 80px;">Total</th>
                        <th style="min-width: 100px;">Método Pago</th>
                        <th style="min-width: 110px;">Tipo Documento</th>
                        <th style="min-width: 120px;">Número Documento</th>
                    </tr>
                </thead>
                <tbody>
                    ${records.slice(0, 10).map(record => `
                        <tr>
                            <td><strong>#${record.id || 'N/A'}</strong></td>
                            <td>${record.fecha ? new Date(record.fecha).toLocaleString('es-ES', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }) : 'N/A'}</td>
                            <td>${record.vendedor || 'N/A'}</td>
                            <td style="font-size: 0.8rem; color: rgba(255,255,255,0.7);">${record.vendedor_email || 'N/A'}</td>
                            <td><strong style="color: #00ff7f;">$${parseFloat(record.total || 0).toFixed(2)}</strong></td>
                            <td><span style="text-transform: capitalize; background: rgba(255,255,255,0.1); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">${record.metodo_pago || 'N/A'}</span></td>
                            <td><span style="text-transform: capitalize; background: rgba(102, 126, 234, 0.2); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">${record.tipo_documento || 'N/A'}</span></td>
                            <td><code style="background: rgba(0,0,0,0.3); padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.8rem;">${record.numero_documento || 'N/A'}</code></td>
                        </tr>
                    `).join('')}
                    ${records.length > 10 ? `
                        <tr style="background: rgba(102, 126, 234, 0.1); border-top: 2px solid rgba(102, 126, 234, 0.3);">
                            <td colspan="8" style="text-align: center; font-style: italic; color: rgba(255,255,255,0.8); padding: 1rem; font-weight: 600;">
                                <i class="bi bi-three-dots"></i> y ${records.length - 10} registros más en el archivo completo
                            </td>
                        </tr>
                    ` : ''}
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 1rem; padding: 1rem; background: rgba(0, 200, 81, 0.1); border-radius: 8px; border: 1px solid rgba(0, 200, 81, 0.3);">
            <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 0.9rem;">
                <i class="bi bi-info-circle" style="color: #00c851;"></i>
                <strong>Formato CSV:</strong> El archivo se descargará con separadores de coma y codificación UTF-8, compatible con Excel y Google Sheets.
            </p>
        </div>
    `;
    
    container.innerHTML = html;
}

function displayJSONPreview(container, data) {
    const records = data.records || [];
    const jsonString = JSON.stringify(records, null, 2);
    
    // Colorear sintaxis JSON básica
    const coloredJson = jsonString
        .replace(/(".*?"):/g, '<span class="json-key">$1</span>:')
        .replace(/: (".*?")/g, ': <span class="json-string">$1</span>')
        .replace(/: (\d+\.?\d*)/g, ': <span class="json-number">$1</span>')
        .replace(/: (true|false)/g, ': <span class="json-boolean">$1</span>')
        .replace(/: (null)/g, ': <span class="json-null">$1</span>');
    
    const html = `
        <div class="preview-info">
            <div class="preview-info-title">📄 Información del Archivo JSON</div>
            <p>Se generará un archivo JSON con <strong>${records.length} registros</strong> de ventas en formato estructurado.</p>
        </div>
        
        <div class="stats-preview">
            <div class="stats-preview-item">
                <div class="stats-preview-value">${records.length}</div>
                <div class="stats-preview-label">Total Registros</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">${Math.round(jsonString.length / 1024)} KB</div>
                <div class="stats-preview-label">Tamaño Aprox.</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">UTF-8</div>
                <div class="stats-preview-label">Codificación</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">JSON</div>
                <div class="stats-preview-label">Formato</div>
            </div>
        </div>
        
        <h4 style="color: white; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-code-square"></i>
            Vista Previa del JSON ${records.length > 3 ? '(primeros 3 registros)' : ''}
        </h4>
        
        <div class="json-preview">${records.length > 3 ? 
            JSON.stringify(records.slice(0, 3), null, 2)
                .replace(/(".*?"):/g, '<span class="json-key">$1</span>:')
                .replace(/: (".*?")/g, ': <span class="json-string">$1</span>')
                .replace(/: (\d+\.?\d*)/g, ': <span class="json-number">$1</span>')
                .replace(/: (true|false)/g, ': <span class="json-boolean">$1</span>')
                .replace(/: (null)/g, ': <span class="json-null">$1</span>')
            + `\n\n// ... y ${records.length - 3} registros más en el archivo completo` :
            coloredJson
        }</div>
    `;
    
    container.innerHTML = html;
}

function displayPDFPreview(container, data) {
    const records = data.records || [];
    const stats = data.stats || {};
    
    let total_general = 0;
    const tableRows = records.slice(0, 15).map(record => {
        total_general += parseFloat(record.total);
        return `
            <tr>
                <td>${record.id}</td>
                <td>${new Date(record.fecha).toLocaleString('es-ES', {
                    day: '2-digit',
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}</td>
                <td>${record.vendedor}</td>
                <td>$${parseFloat(record.total).toFixed(2)}</td>
                <td>${record.metodo_pago.charAt(0).toUpperCase() + record.metodo_pago.slice(1)}</td>
                <td>${record.tipo_documento.charAt(0).toUpperCase() + record.tipo_documento.slice(1)}</td>
                <td>${record.numero_documento}</td>
            </tr>
        `;
    }).join('');
    
    const html = `
        <div class="preview-info">
            <div class="preview-info-title">📑 Información del Archivo PDF</div>
            <p>Se generará un reporte PDF con <strong>${records.length} ventas</strong> en formato profesional.</p>
        </div>
        
        <div class="stats-preview">
            <div class="stats-preview-item">
                <div class="stats-preview-value">${records.length}</div>
                <div class="stats-preview-label">Total Ventas</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">$${parseFloat(stats.total_ingresos || 0).toLocaleString('es-ES', {minimumFractionDigits: 2})}</div>
                <div class="stats-preview-label">Ingresos Totales</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">A4</div>
                <div class="stats-preview-label">Tamaño Página</div>
            </div>
            <div class="stats-preview-item">
                <div class="stats-preview-value">Horizontal</div>
                <div class="stats-preview-label">Orientación</div>
            </div>
        </div>
        
        <h4 style="color: white; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-file-earmark-pdf"></i>
            Vista Previa del Documento ${records.length > 15 ? '(primeras 15 ventas)' : ''}
        </h4>
        
        <div class="pdf-preview-container">
            <div class="header">
                <div class="title">REGISTRO DE VENTAS</div>
                <div class="subtitle">Sistema Bazar - ${new Date().toLocaleString('es-ES')}</div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Vendedor</th>
                        <th>Total</th>
                        <th>Método</th>
                        <th>Tipo Doc.</th>
                        <th>N° Documento</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                    ${records.length > 15 ? `
                        <tr style="background: #f8f9fa; font-style: italic;">
                            <td colspan="7" style="text-align: center; color: #666;">
                                ... y ${records.length - 15} ventas más en el reporte completo
                            </td>
                        </tr>
                    ` : ''}
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTAL GENERAL</strong></td>
                        <td><strong>$${parseFloat(stats.total_ingresos || 0).toFixed(2)}</strong></td>
                        <td colspan="3"><strong>${records.length} ventas</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="footer">
                Generado el ${new Date().toLocaleString('es-ES')} - Sistema Bazar POS
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    modal.style.display = 'none';
}

// Cerrar modal de previsualización al hacer clic fuera
window.addEventListener('click', function(event) {
    const modal = document.getElementById('previewModal');
    if (event.target === modal) {
        closePreviewModal();
    }
});
</script>
