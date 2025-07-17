<?php 
// Handle exports first, before any output
require_once '../../includes/db.php';
require_once '../../includes/funciones.php'; // CSRF helpers

// Function to get filtered sales data (defined at top level)
function obtenerVentasDia($conn, $fecha_ini, $fecha_fin, $vendedor_id = '') {
    $filter = '';
    $params = [$fecha_ini, $fecha_fin];
    if ($vendedor_id) { 
        $filter = ' AND v.usuario_id = ?'; 
        $params[] = $vendedor_id; 
    }
    $sql = "SELECT v.id as boleta_id, DATE(v.fecha) as fecha, v.total, v.tipo_documento, v.numero_documento, u.nombre as vendedor
            FROM ventas v
            JOIN usuarios u ON v.usuario_id = u.id
            WHERE DATE(v.fecha) BETWEEN ? AND ?" . $filter . "
            ORDER BY v.fecha DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check for export request first
$export_ventas = $_GET['export_ventas'] ?? '';
if ($export_ventas) {
    // Get filter parameters for export
    $venta_inicio = $_GET['venta_inicio'] ?? date('Y-m-d');
    $venta_fin = $_GET['venta_fin'] ?? date('Y-m-d');
    $vendedor_sel = $_GET['vendedor'] ?? '';
    
    $ventasData = obtenerVentasDia($conn, $venta_inicio, $venta_fin, $vendedor_sel);
    
    if ($export_ventas === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="ventas.json"');
        echo json_encode($ventasData, JSON_PRETTY_PRINT);
        exit;
    }
    if ($export_ventas === 'excel') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="ventas.csv"');
        $out = fopen('php://output','w');
        fputcsv($out, ['Boleta ID','Fecha','Vendedor','Total','Tipo','Número Documento']);
        foreach($ventasData as $row) {
            fputcsv($out, [$row['boleta_id'],$row['fecha'],$row['vendedor'],$row['total'],$row['tipo_documento'],$row['numero_documento']]);
        }
        fclose($out);
        exit;
    }
}

// Now include layout and continue with normal page logic
require_once '../../includes/layout_admin.php';

// CSRF validation en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    verify_csrf_token($_POST['csrf_token'] ?? '');
}

// Obtener sesión de caja actual
function obtenerSesionActual($conn, $usuario_id = null) {
    $where = $usuario_id ? "usuario_id = ?" : "estado = 'abierta'";
    $query = "SELECT sc.*, u.nombre as vendedor 
              FROM sesiones_caja sc 
              JOIN usuarios u ON sc.usuario_id = u.id 
              WHERE $where 
              ORDER BY sc.fecha_apertura DESC 
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    if ($usuario_id) {
        $stmt->execute([$usuario_id]);
    }
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'abrir_caja':
            $usuario_id = (int)$_POST['usuario_id'];
            $monto_inicial = (float)$_POST['monto_inicial'];
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Verificar que el usuario no tenga una caja abierta
            $sesionActual = obtenerSesionActual($conn, $usuario_id);
            if ($sesionActual && $sesionActual['estado'] === 'abierta') {
                $error = "Este usuario ya tiene una caja abierta";
            } else {
                $stmt = $conn->prepare("INSERT INTO sesiones_caja (usuario_id, monto_inicial, observaciones) VALUES (?, ?, ?)");
                if ($stmt->execute([$usuario_id, $monto_inicial, $observaciones])) {
                    $success = "Caja abierta exitosamente";
                } else {
                    $error = "Error al abrir caja: " . $conn->error;
                }
            }
            break;
            
        case 'cerrar_caja':
            $sesion_id = (int)$_POST['sesion_id'];
            $monto_final = (float)$_POST['monto_final'];
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Calcular total de ventas de la sesión
            $ventasQuery = "SELECT COALESCE(SUM(v.total), 0) as total_ventas 
                           FROM ventas v 
                           JOIN sesiones_caja sc ON DATE(v.fecha) = DATE(sc.fecha_apertura) 
                           WHERE sc.id = ? AND v.usuario_id = sc.usuario_id";
            $ventasStmt = $conn->prepare($ventasQuery);
            $ventasStmt->execute([$sesion_id]);
            $totalVentas = $ventasStmt->fetch(PDO::FETCH_ASSOC)['total_ventas'];
            
            $stmt = $conn->prepare("UPDATE sesiones_caja SET fecha_cierre = NOW(), monto_final = ?, total_ventas = ?, estado = 'cerrada', observaciones = CONCAT(COALESCE(observaciones, ''), ?, ' | Cerrada: ', ?) WHERE id = ?");
            $fechaCierre = date('Y-m-d H:i:s');
            if ($stmt->execute([$monto_final, $totalVentas, $observaciones, $fechaCierre, $sesion_id])) {
                $success = "Caja cerrada exitosamente";
            } else {
                $error = "Error al cerrar caja: " . $conn->error;
            }
            break;
    }
}

// Obtener vendedores para el selector
$vendedoresQuery = "SELECT id, nombre FROM usuarios WHERE rol IN ('vendedor', 'jefe') ORDER BY nombre";
$vendedores = $conn->query($vendedoresQuery)->fetchAll(PDO::FETCH_ASSOC);

// Obtener sesiones recientes
$sesionesQuery = "SELECT sc.*, u.nombre as vendedor 
                  FROM sesiones_caja sc 
                  JOIN usuarios u ON sc.usuario_id = u.id 
                  ORDER BY sc.fecha_apertura DESC 
                  LIMIT 20";
$sesiones = $conn->query($sesionesQuery)->fetchAll(PDO::FETCH_ASSOC);

// Obtener sesiones abiertas
$sesionesAbiertasQuery = "SELECT sc.*, u.nombre as vendedor 
                          FROM sesiones_caja sc 
                          JOIN usuarios u ON sc.usuario_id = u.id 
                          WHERE sc.estado = 'abierta' 
                          ORDER BY sc.fecha_apertura DESC";
$sesionesAbiertas = $conn->query($sesionesAbiertasQuery)->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas del día
$hoy = date('Y-m-d');
$statsQuery = "SELECT 
    COUNT(*) as total_sesiones,
    SUM(CASE WHEN estado = 'abierta' THEN 1 ELSE 0 END) as sesiones_abiertas,
    SUM(CASE WHEN estado = 'cerrada' THEN 1 ELSE 0 END) as sesiones_cerradas,
    SUM(CASE WHEN estado = 'cerrada' THEN total_ventas ELSE 0 END) as total_ventas_dia
FROM sesiones_caja 
WHERE DATE(fecha_apertura) = '$hoy'";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Parámetros de filtro de ventas
$venta_inicio = $_GET['venta_inicio'] ?? date('Y-m-d');
$venta_fin = $_GET['venta_fin'] ?? date('Y-m-d');
$vendedor_sel = $_GET['vendedor'] ?? '';

// Obtener datos para vista
$ventasRows = obtenerVentasDia($conn, $venta_inicio, $venta_fin, $vendedor_sel);

// Cálculo de estadísticas de ventas filtradas
$totalesVentas = array_column($ventasRows, 'total');
$countVentas = count($totalesVentas);
$sumaVentas = array_sum($totalesVentas);
$promedioVentas = $countVentas > 0 ? ($sumaVentas / $countVentas) : 0;
$maxVenta = $countVentas > 0 ? max($totalesVentas) : 0;
$minVenta = $countVentas > 0 ? min($totalesVentas) : 0;

// Obtener lista de vendedores
$vendedoresList = $conn->query("SELECT id,nombre FROM usuarios WHERE rol IN ('vendedor','jefe') ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.caja-container {
    padding: 0;
}

.caja-header {
    background: linear-gradient(135deg, rgba(45, 27, 105, 0.1), rgba(17, 153, 142, 0.1));
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.caja-title {
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

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.stat-icon.sessions { background: linear-gradient(135deg, #48dbfb, #74edf7); }
.stat-icon.open { background: linear-gradient(135deg, #00ff7f, #7bed9f); }
.stat-icon.closed { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
.stat-icon.sales { background: linear-gradient(135deg, #feca57, #ffd93d); }

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

.actions-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.action-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(15px);
}

.action-title {
    color: #ffffff;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    background: rgba(30, 30, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 0.75rem;
    color: white;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #48dbfb;
    box-shadow: 0 0 0 2px rgba(72, 219, 251, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, #00ff7f, #7bed9f);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-success:hover, .btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.sessions-section {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(15px);
}

.section-title {
    color: #ffffff;
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sessions-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tab-btn {
    background: transparent;
    border: none;
    padding: 1rem 2rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn.active {
    color: #48dbfb;
    border-bottom-color: #48dbfb;
}

.sessions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
}

.session-card {
    background: rgba(20, 20, 30, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.session-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 255, 255, 0.2);
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.session-vendor {
    color: #ffffff;
    font-weight: 600;
    font-size: 1.1rem;
}

.session-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-abierta {
    background: rgba(0, 255, 127, 0.2);
    color: #00ff7f;
    border: 1px solid rgba(0, 255, 127, 0.3);
}

.status-cerrada {
    background: rgba(255, 107, 107, 0.2);
    color: #ff6b6b;
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.session-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    color: #ffffff;
    font-weight: 600;
}

.session-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.btn-sm {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-close {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
}

.btn-view {
    background: linear-gradient(135deg, #48dbfb, #0abde3);
    color: white;
}

.btn-sm:hover {
    transform: scale(1.05);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
}

.modal-content {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.95), rgba(45, 27, 105, 0.3));
    margin: 5% auto;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    backdrop-filter: blur(15px);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.modal-title {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.close {
    color: rgba(255, 255, 255, 0.7);
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    border: none;
    background: none;
}

.close:hover {
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .actions-section {
        grid-template-columns: 1fr;
    }
    
    .sessions-tabs {
        flex-direction: column;
    }
    
    .sessions-grid {
        grid-template-columns: 1fr;
    }
    
    .session-details {
        grid-template-columns: 1fr;
    }
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid;
    backdrop-filter: blur(10px);
}

.alert-success {
    background: rgba(0, 255, 127, 0.1);
    border-color: rgba(0, 255, 127, 0.3);
    color: #00ff7f;
}

.alert-error {
    background: rgba(255, 107, 107, 0.1);
    border-color: rgba(255, 107, 107, 0.3);
    color: #ff6b6b;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
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

.btn-generate {
    background: linear-gradient(135deg, #00ff7f, #7bed9f);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-export {
    background: linear-gradient(135deg, #feca57, #ffd93d);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-generate:hover, .btn-export:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.table-container {
    background: rgba(30, 30, 40, 0.9);
    border-radius: 12px;
    overflow: hidden;
    margin-top: 1rem;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table th, .report-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.report-table th {
    background: rgba(45, 27, 105, 0.3);
    color: #ffffff;
    font-weight: 600;
}

.report-table td {
    color: rgba(255, 255, 255, 0.9);
}

.report-table tr:hover {
    background: rgba(255, 255, 255, 0.05);
}
</style>

<div class="caja-container">
    <!-- Header -->
    <div class="caja-header">
        <h1 class="caja-title">Control de Caja</h1>
        <p style="color: rgba(255, 255, 255, 0.7); margin: 0;">
            Gestiona la apertura y cierre de cajas para el control de ventas diarias
        </p>
    </div>

    <!-- Estadísticas del día -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon sessions">📊</div>
            <div class="stat-value"><?= $stats['total_sesiones'] ?></div>
            <div class="stat-label">Sesiones del Día</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon open">✅</div>
            <div class="stat-value"><?= $stats['sesiones_abiertas'] ?></div>
            <div class="stat-label">Cajas Abiertas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon closed">🔒</div>
            <div class="stat-value"><?= $stats['sesiones_cerradas'] ?></div>
            <div class="stat-label">Cajas Cerradas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon sales">💰</div>
            <div class="stat-value">$<?= number_format($stats['total_ventas_dia'], 0, ',', '.') ?></div>
            <div class="stat-label">Total Ventas Día</div>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Acciones de Caja -->
    <div class="actions-section">
        <!-- Abrir Caja -->
        <div class="action-card">
            <h3 class="action-title">
                <i class="bi bi-unlock"></i>
                Abrir Caja
            </h3>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="abrir_caja">
                
                <div class="form-group">
                    <label class="form-label">Vendedor</label>
                    <select name="usuario_id" class="form-control" required>
                        <option value="">Seleccionar vendedor</option>
                        <?php foreach ($vendedores as $vendedor): ?>
                            <option value="<?= $vendedor['id'] ?>"><?= htmlspecialchars($vendedor['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Monto Inicial</label>
                    <input type="number" name="monto_inicial" class="form-control" 
                           step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3" 
                              placeholder="Observaciones sobre la apertura..."></textarea>
                </div>
                
                <button type="submit" class="btn-success">
                    <i class="bi bi-unlock"></i> Abrir Caja
                </button>
            </form>
        </div>

        <!-- Cerrar Caja -->
        <div class="action-card">
            <h3 class="action-title">
                <i class="bi bi-lock"></i>
                Cerrar Caja
            </h3>
            <?php if (!empty($sesionesAbiertas)): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="action" value="cerrar_caja">
                    
                    <div class="form-group">
                        <label class="form-label">Sesión a Cerrar</label>
                        <select name="sesion_id" class="form-control" required>
                            <option value="">Seleccionar sesión</option>
                            <?php foreach ($sesionesAbiertas as $sesion): ?>
                                <option value="<?= $sesion['id'] ?>">
                                    <?= htmlspecialchars($sesion['vendedor']) ?> - 
                                    Abierta: <?= date('d/m/Y H:i', strtotime($sesion['fecha_apertura'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Monto Final en Caja</label>
                        <input type="number" name="monto_final" class="form-control" 
                               step="0.01" min="0" required placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Observaciones de Cierre</label>
                        <textarea name="observaciones" class="form-control" rows="3" 
                                  placeholder="Observaciones sobre el cierre..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-danger">
                        <i class="bi bi-lock"></i> Cerrar Caja
                    </button>
                </form>
            <?php else: ?>
                <p style="color: rgba(255, 255, 255, 0.7); text-align: center; padding: 2rem;">
                    No hay cajas abiertas para cerrar
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial de Sesiones -->
    <div class="sessions-section">
        <h3 class="section-title">
            <i class="bi bi-clock-history"></i>
            Historial de Sesiones
        </h3>
        
        <div class="sessions-tabs">
            <button class="tab-btn active" onclick="showSessions('todas')">Todas</button>
            <button class="tab-btn" onclick="showSessions('abiertas')">Abiertas</button>
            <button class="tab-btn" onclick="showSessions('cerradas')">Cerradas</button>
        </div>
        
        <div class="sessions-grid" id="sessionsContainer">
            <?php foreach ($sesiones as $sesion): ?>
                <div class="session-card session-<?= $sesion['estado'] ?>">
                    <div class="session-header">
                        <div class="session-vendor"><?= htmlspecialchars($sesion['vendedor']) ?></div>
                        <div class="session-status status-<?= $sesion['estado'] ?>">
                            <?= ucfirst($sesion['estado']) ?>
                        </div>
                    </div>
                    
                    <div class="session-details">
                        <div class="detail-item">
                            <span class="detail-label">Apertura</span>
                            <span class="detail-value"><?= date('d/m/Y H:i', strtotime($sesion['fecha_apertura'])) ?></span>
                        </div>
                        
                        <?php if ($sesion['fecha_cierre']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Cierre</span>
                            <span class="detail-value"><?= date('d/m/Y H:i', strtotime($sesion['fecha_cierre'])) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-item">
                            <span class="detail-label">Monto Inicial</span>
                            <span class="detail-value">$<?= number_format($sesion['monto_inicial'], 0, ',', '.') ?></span>
                        </div>
                        
                        <?php if ($sesion['estado'] === 'cerrada'): ?>
                        <div class="detail-item">
                            <span class="detail-label">Monto Final</span>
                            <span class="detail-value">$<?= number_format($sesion['monto_final'], 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Total Ventas</span>
                            <span class="detail-value">$<?= number_format($sesion['total_ventas'], 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Diferencia</span>
                            <span class="detail-value" style="color: <?= ($sesion['monto_final'] - $sesion['monto_inicial'] - $sesion['total_ventas']) >= 0 ? '#00ff7f' : '#ff6b6b' ?>">
                                $<?= number_format($sesion['monto_final'] - $sesion['monto_inicial'] - $sesion['total_ventas'], 0, ',', '.') ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="session-actions">
                        <?php if ($sesion['estado'] === 'abierta'): ?>
                            <button class="btn-sm btn-close" onclick="cerrarSesion(<?= $sesion['id'] ?>, '<?= htmlspecialchars($sesion['vendedor']) ?>')">
                                <i class="bi bi-lock"></i> Cerrar
                            </button>
                        <?php endif; ?>
                        <button class="btn-sm btn-view" onclick="verDetalles(<?= $sesion['id'] ?>)">
                            <i class="bi bi-eye"></i> Ver
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Resumen Analítico de Ventas -->
    <div class="report-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon sales">💰</div>
                <div class="stat-value">$<?= number_format($sumaVentas,2,',','.') ?></div>
                <div class="stat-label">Total Ventas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon transactions">📊</div>
                <div class="stat-value"><?= $countVentas ?></div>
                <div class="stat-label">Transacciones</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon average">📈</div>
                <div class="stat-value">$<?= number_format($promedioVentas,2,',','.') ?></div>
                <div class="stat-label">Venta Promedio</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon sales">⚖️</div>
                <div class="stat-value">$<?= number_format($maxVenta,2,',','.') ?></div>
                <div class="stat-label">Venta Máxima</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon sales">🔽</div>
                <div class="stat-value">$<?= number_format($minVenta,2,',','.') ?></div>
                <div class="stat-label">Venta Mínima</div>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="ventasChart"></canvas>
        </div>
    </div>

    <!-- Filtros de Ventas Diarias -->
    <div class="sales-section">
        <h3 class="section-title"><i class="bi bi-cash-stack"></i> Ventas Diarias</h3>
        <form method="GET" class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Fecha Inicio</label>
                <input type="date" name="venta_inicio" class="filter-input" value="<?= $venta_inicio ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Fecha Fin</label>
                <input type="date" name="venta_fin" class="filter-input" value="<?= $venta_fin ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Vendedor</label>
                <select name="vendedor" class="filter-select">
                    <option value="" <?= $vendedor_sel==''?'selected':'' ?>>Todos</option>
                    <?php foreach($vendedoresList as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $vendedor_sel==$v['id']?'selected':'' ?>><?= htmlspecialchars($v['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn-generate"><i class="bi bi-filter"></i> Filtrar</button>
            </div>
            <div class="filter-group">
                <button type="submit" name="export_ventas" value="json" class="btn-export"><i class="bi bi-file-earmark-code"></i> JSON</button>
                <button type="submit" name="export_ventas" value="excel" class="btn-export"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</button>
            </div>
        </form>
        <div class="table-container">
            <table class="report-table">
                <thead><tr><th>Boleta</th><th>Fecha</th><th>Vendedor</th><th>Total</th><th>Tipo</th><th>n° Documento</th></tr></thead>
                <tbody>
                    <?php foreach($ventasRows as $r): ?>
                    <tr>
                        <td><?= $r['boleta_id'] ?></td>
                        <td><?= $r['fecha'] ?></td>
                        <td><?= htmlspecialchars($r['vendedor']) ?></td>
                        <td>$<?= number_format($r['total'],0,',','.') ?></td>
                        <td><?= htmlspecialchars($r['tipo_documento']) ?></td>
                        <td><?= htmlspecialchars($r['numero_documento']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function showSessions(tipo) {
    const sessions = document.querySelectorAll('.session-card');
    const tabs = document.querySelectorAll('.tab-btn');
    
    // Actualizar tabs activos
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filtrar sesiones
    sessions.forEach(session => {
        if (tipo === 'todas') {
            session.style.display = 'block';
        } else {
            const isMatch = session.classList.contains(`session-${tipo === 'abiertas' ? 'abierta' : 'cerrada'}`);
            session.style.display = isMatch ? 'block' : 'none';
        }
    });
}

function cerrarSesion(sesionId, vendedor) {
    if (confirm(`¿Cerrar la sesión de caja de ${vendedor}?`)) {
        // Aquí podrías abrir un modal específico para cerrar la caja
        // o redirigir a un formulario de cierre
        alert('Función de cierre rápido en desarrollo. Use el formulario de "Cerrar Caja".');
    }
}

function verDetalles(sesionId) {
    // Abrir nueva pestaña con ventas de la sesión
    window.open('ventas_sesion.php?sesion_id=' + sesionId, '_blank');
}

// Datos para gráfico de ventas
const ventasData = <?= json_encode($ventasRows) ?>;
const labels = ventasData.map(v => v.fecha);
const datos = ventasData.map(v => parseFloat(v.total));
new Chart(document.getElementById('ventasChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Total Ventas',
            data: datos,
            backgroundColor: 'rgba(72, 219, 251, 0.5)',
            borderColor: '#48dbfb',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>
