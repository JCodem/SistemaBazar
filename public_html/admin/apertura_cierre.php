<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';

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
        $stmt->bind_param("i", $usuario_id);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
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
                $stmt->bind_param("ids", $usuario_id, $monto_inicial, $observaciones);
                
                if ($stmt->execute()) {
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
            $ventasStmt->bind_param("i", $sesion_id);
            $ventasStmt->execute();
            $totalVentas = $ventasStmt->get_result()->fetch_assoc()['total_ventas'];
            
            $stmt = $conn->prepare("UPDATE sesiones_caja SET fecha_cierre = NOW(), monto_final = ?, total_ventas = ?, estado = 'cerrada', observaciones = CONCAT(COALESCE(observaciones, ''), ?, ' | Cerrada: ', ?) WHERE id = ?");
            $fechaCierre = date('Y-m-d H:i:s');
            $stmt->bind_param("ddssi", $monto_final, $totalVentas, $observaciones, $fechaCierre, $sesion_id);
            
            if ($stmt->execute()) {
                $success = "Caja cerrada exitosamente";
            } else {
                $error = "Error al cerrar caja: " . $conn->error;
            }
            break;
    }
}

// Obtener vendedores para el selector
$vendedoresQuery = "SELECT id, nombre FROM usuarios WHERE rol IN ('vendedor', 'jefe') ORDER BY nombre";
$vendedores = $conn->query($vendedoresQuery)->fetch_all(MYSQLI_ASSOC);

// Obtener sesiones recientes
$sesionesQuery = "SELECT sc.*, u.nombre as vendedor 
                  FROM sesiones_caja sc 
                  JOIN usuarios u ON sc.usuario_id = u.id 
                  ORDER BY sc.fecha_apertura DESC 
                  LIMIT 20";
$sesiones = $conn->query($sesionesQuery)->fetch_all(MYSQLI_ASSOC);

// Obtener sesiones abiertas
$sesionesAbiertasQuery = "SELECT sc.*, u.nombre as vendedor 
                          FROM sesiones_caja sc 
                          JOIN usuarios u ON sc.usuario_id = u.id 
                          WHERE sc.estado = 'abierta' 
                          ORDER BY sc.fecha_apertura DESC";
$sesionesAbiertas = $conn->query($sesionesAbiertasQuery)->fetch_all(MYSQLI_ASSOC);

// Estadísticas del día
$hoy = date('Y-m-d');
$statsQuery = "SELECT 
    COUNT(*) as total_sesiones,
    SUM(CASE WHEN estado = 'abierta' THEN 1 ELSE 0 END) as sesiones_abiertas,
    SUM(CASE WHEN estado = 'cerrada' THEN 1 ELSE 0 END) as sesiones_cerradas,
    SUM(CASE WHEN estado = 'cerrada' THEN total_ventas ELSE 0 END) as total_ventas_dia
FROM sesiones_caja 
WHERE DATE(fecha_apertura) = '$hoy'";
$stats = $conn->query($statsQuery)->fetch_assoc();
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
</div>

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
    // Implementar modal con detalles completos de la sesión
    alert(`Ver detalles de la sesión ${sesionId} - Función en desarrollo`);
}

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
