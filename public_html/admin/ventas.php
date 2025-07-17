<?php
// Handle exports first, before any output
require_once '../../includes/db.php';
require_once '../../includes/funciones.php'; // CSRF helpers

// Import DomPDF classes at the top level
use Dompdf\Dompdf;
use Dompdf\Options;

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
        require_once '../../vendor/autoload.php';
        
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
$stmt->execute(array_merge($params, [$limit, $offset]));
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
            <a href="?export=csv<?= http_build_query(array_filter(['search' => $search, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'usuario' => $usuario_filter, 'tipo_documento' => $tipo_documento_filter, 'metodo_pago' => $metodo_pago_filter]), '', '&') ?>" 
               class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> Exportar CSV
            </a>
            <a href="?export=json<?= http_build_query(array_filter(['search' => $search, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'usuario' => $usuario_filter, 'tipo_documento' => $tipo_documento_filter, 'metodo_pago' => $metodo_pago_filter]), '', '&') ?>" 
               class="btn btn-warning">
                <i class="bi bi-file-earmark-code"></i> Exportar JSON
            </a>
            <a href="?export=pdf<?= http_build_query(array_filter(['search' => $search, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin, 'usuario' => $usuario_filter, 'tipo_documento' => $tipo_documento_filter, 'metodo_pago' => $metodo_pago_filter]), '', '&') ?>" 
               class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
            </a>
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
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ventas)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: rgba(255,255,255,0.5);">
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
                        <td><code><?= htmlspecialchars($venta['numero_documento']) ?></code></td>
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
</script>
