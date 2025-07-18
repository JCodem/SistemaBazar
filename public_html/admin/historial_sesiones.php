<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';
require_once '../../includes/funciones.php';

$titulo = "Historial de Sesiones";

// Obtener vendedores para el filtro
$vendedoresQuery = "SELECT id, nombre FROM usuarios WHERE rol IN ('vendedor', 'jefe') ORDER BY nombre";
$vendedores = $conn->query($vendedoresQuery)->fetchAll(PDO::FETCH_ASSOC);

// Parámetros de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$vendedor_id = $_GET['vendedor_id'] ?? '';
$estado = $_GET['estado'] ?? '';

// Obtener datos para los widgets del dashboard
// 1. Ventas de hoy

// Construir consulta con filtros para el historial
$where_clauses = [];
$params = [];

if ($fecha_inicio) {
    $where_clauses[] = "DATE(sc.fecha_apertura) >= ?";
    $params[] = $fecha_inicio;
}

if ($fecha_fin) {
    $where_clauses[] = "DATE(sc.fecha_apertura) <= ?";
    $params[] = $fecha_fin;
}

if ($vendedor_id) {
    $where_clauses[] = "sc.usuario_id = ?";
    $params[] = $vendedor_id;
}

if ($estado) {
    $where_clauses[] = "sc.estado = ?";
    $params[] = $estado;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Obtener sesiones con filtros
$sesionesQuery = "SELECT sc.*, u.nombre as vendedor 
                  FROM sesiones_caja sc 
                  JOIN usuarios u ON sc.usuario_id = u.id 
                  $where_sql
                  ORDER BY sc.fecha_apertura DESC 
                  LIMIT 100";
$stmt = $conn->prepare($sesionesQuery);
$stmt->execute($params);
$sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas del historial filtrado
$statsQuery = "SELECT 
    COUNT(*) as total_sesiones,
    SUM(CASE WHEN estado = 'abierta' THEN 1 ELSE 0 END) as sesiones_abiertas,
    SUM(CASE WHEN estado = 'cerrada' THEN 1 ELSE 0 END) as sesiones_cerradas,
    SUM(CASE WHEN estado = 'cerrada' THEN total_ventas ELSE 0 END) as total_ventas,
    AVG(CASE WHEN estado = 'cerrada' THEN total_ventas ELSE 0 END) as promedio_ventas,
    SUM(CASE WHEN estado = 'cerrada' THEN monto_final ELSE 0 END) as total_efectivo
FROM sesiones_caja sc 
$where_sql";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute($params);
$statsHistorial = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
.historial-container {
    padding: 0;
}

.historial-header {
    background: linear-gradient(135deg, rgba(45, 27, 105, 0.1), rgba(17, 153, 142, 0.1));
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.historial-title {
    color: #ffffff;
    font-size: 2.2rem;
    font-weight: 300;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #ff6b6b, #feca57, #48dbfb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
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

.btn-filter {
    background: linear-gradient(135deg, #48dbfb, #0abde3);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
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

.stat-icon.total { background: linear-gradient(135deg, #48dbfb, #74edf7); }
.stat-icon.open { background: linear-gradient(135deg, #00ff7f, #7bed9f); }
.stat-icon.closed { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
.stat-icon.sales { background: linear-gradient(135deg, #feca57, #ffd93d); }
.stat-icon.average { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
.stat-icon.cash { background: linear-gradient(135deg, #10b981, #34d399); }

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

/* Dashboard Widgets Styles */
.dashboard-widgets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.widget-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(15px);
    transition: all 0.3s ease;
}

.widget-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 255, 255, 0.2);
}

.widget-card.clickable {
    cursor: pointer;
}

.widget-card.clickable:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.widget-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.widget-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
}

.widget-icon.ventas { background: linear-gradient(135deg, #00ff7f, #7bed9f); }
.widget-icon.vendedores { background: linear-gradient(135deg, #48dbfb, #74edf7); }
.widget-icon.productos { background: linear-gradient(135deg, #feca57, #ffd93d); }
.widget-icon.transacciones { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }

.widget-title {
    color: #ffffff;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
}

.widget-content {
    color: rgba(255, 255, 255, 0.9);
}

.widget-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.widget-subtitle {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.6);
}

/* Productos Widget Styles */
.productos-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.producto-stat {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.producto-stat:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: scale(1.05);
}

.producto-stat.clickable:hover {
    border-color: rgba(255, 255, 255, 0.3);
}

.producto-stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.producto-stat-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
}

.producto-stat.total .producto-stat-value { color: #48dbfb; }
.producto-stat.stock .producto-stat-value { color: #00ff7f; }
.producto-stat.bajo .producto-stat-value { color: #feca57; }
.producto-stat.sin-stock .producto-stat-value { color: #ff6b6b; }

/* Chart Styles */
.chart-container {
    margin-top: 1.5rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.chart-wrapper {
    position: relative;
    height: 250px;
    display: flex;
    align-items: end;
    justify-content: space-around;
    padding: 1rem 0;
}

.chart-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px 4px 0 0;
    min-width: 40px;
    margin: 0 2px;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.chart-bar:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6b4190 100%);
    transform: scaleY(1.05);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.chart-bar-label {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.7);
    white-space: nowrap;
}

.chart-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    color: #ffffff;
    font-weight: 600;
    white-space: nowrap;
}

.chart-legend {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.chart-legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
}

.chart-legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    background: linear-gradient(135deg, #8b5cf6, #a78bfa);
}

.sessions-section {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
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

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination a, .pagination span {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(30, 30, 40, 0.8);
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: rgba(72, 219, 251, 0.2);
    border-color: #48dbfb;
}

.pagination .current {
    background: #48dbfb;
    border-color: #48dbfb;
}

/* Responsive */
@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .sessions-grid {
        grid-template-columns: 1fr;
    }
    
    .session-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="historial-container">
    <!-- Header -->
    <div class="historial-header">
        <h1 class="historial-title">Historial de Sesiones de Caja</h1>
        <p style="color: rgba(255, 255, 255, 0.7); margin: 0;">
            Consulta y analiza el histórico completo de sesiones de caja
        </p>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <h3 class="section-title">
            <i class="bi bi-funnel"></i>
            Filtros de Búsqueda
        </h3>
        
        <form method="GET" class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="filter-input" value="<?= $fecha_inicio ?>">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="filter-input" value="<?= $fecha_fin ?>">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Vendedor</label>
                <select name="vendedor_id" class="filter-select">
                    <option value="">Todos los vendedores</option>
                    <?php foreach ($vendedores as $vendedor): ?>
                        <option value="<?= $vendedor['id'] ?>" <?= $vendedor_id == $vendedor['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vendedor['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Estado</label>
                <select name="estado" class="filter-select">
                    <option value="">Todos los estados</option>
                    <option value="abierta" <?= $estado === 'abierta' ? 'selected' : '' ?>>Abiertas</option>
                    <option value="cerrada" <?= $estado === 'cerrada' ? 'selected' : '' ?>>Cerradas</option>
                </select>
            </div>
            
            <div class="filter-group">
                <button type="submit" class="btn-filter">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
        </form>
    </div>


    <!-- Lista de sesiones -->
    <div class="sessions-section">
        <h3 class="section-title">
            <i class="bi bi-clock-history"></i>
            Sesiones Encontradas (<?= count($sesiones) ?>)
        </h3>
        
        <div class="sessions-grid">
            <?php foreach ($sesiones as $sesion): ?>
                <div class="session-card">
                    <div class="session-header">
                        <div class="session-vendor"><?= htmlspecialchars($sesion['vendedor']) ?></div>
                        <div class="session-status status-<?= $sesion['estado'] ?>">
                            <?= ucfirst($sesion['estado']) ?>
                        </div>
                    </div>
                    
                    <div class="session-details">
                        <div class="detail-item">
                            <div class="detail-label">Fecha Apertura</div>
                            <div class="detail-value"><?= date('d/m/Y H:i', strtotime($sesion['fecha_apertura'])) ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Monto Inicial</div>
                            <div class="detail-value">$<?= number_format($sesion['monto_inicial'], 0, ',', '.') ?></div>
                        </div>
                        
                        <?php if ($sesion['estado'] === 'cerrada'): ?>
                            <div class="detail-item">
                                <div class="detail-label">Fecha Cierre</div>
                                <div class="detail-value">
                                    <?= $sesion['fecha_cierre'] ? date('d/m/Y H:i', strtotime($sesion['fecha_cierre'])) : 'N/A' ?>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Monto Final</div>
                                <div class="detail-value">$<?= number_format($sesion['monto_final'], 0, ',', '.') ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Total Ventas</div>
                                <div class="detail-value">$<?= number_format($sesion['total_ventas'], 0, ',', '.') ?></div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Diferencia</div>
                                <div class="detail-value" style="color: <?= ($sesion['monto_final'] - $sesion['monto_inicial'] - $sesion['total_ventas']) >= 0 ? '#00ff7f' : '#ff6b6b' ?>">
                                    $<?= number_format($sesion['monto_final'] - $sesion['monto_inicial'] - $sesion['total_ventas'], 0, ',', '.') ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="detail-item">
                                <div class="detail-label">Duración</div>
                                <div class="detail-value">
                                    <?php
                                    $inicio = new DateTime($sesion['fecha_apertura']);
                                    $ahora = new DateTime();
                                    $duracion = $inicio->diff($ahora);
                                    echo $duracion->format('%h:%I horas');
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($sesion['observaciones']): ?>
                        <div class="detail-item" style="margin-top: 1rem;">
                            <div class="detail-label">Observaciones</div>
                            <div class="detail-value" style="font-size: 0.85rem; color: rgba(255, 255, 255, 0.8);">
                                <?= htmlspecialchars($sesion['observaciones']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($sesiones)): ?>
            <div style="text-align: center; padding: 3rem; color: rgba(255, 255, 255, 0.7);">
                <i class="bi bi-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                <h3>No se encontraron sesiones</h3>
                <p>Ajusta los filtros para encontrar las sesiones que buscas.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-hide alerts y efectos suaves
document.addEventListener('DOMContentLoaded', function() {
    // Efectos hover para las tarjetas
    const cards = document.querySelectorAll('.session-card, .stat-card, .widget-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (this.classList.contains('widget-card')) {
                this.style.transform = 'translateY(-8px)';
            } else {
                this.style.transform = 'translateY(-8px)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (this.classList.contains('widget-card')) {
                this.style.transform = 'translateY(-4px)';
            } else {
                this.style.transform = 'translateY(-4px)';
            }
        });
    });

    // Animación para las barras del gráfico
    const bars = document.querySelectorAll('.chart-bar');
    bars.forEach((bar, index) => {
        setTimeout(() => {
            bar.style.opacity = '0';
            bar.style.transform = 'scaleY(0)';
            bar.style.transition = 'all 0.6s ease';
            setTimeout(() => {
                bar.style.opacity = '1';
                bar.style.transform = 'scaleY(1)';
            }, 100);
        }, index * 100);
    });
});

// Función para redirigir a informes del día actual
function redirectToInformes() {
    const today = new Date().toISOString().split('T')[0];
    window.location.href = `informes.php?fecha_inicio=${today}&fecha_fin=${today}&tipo=dia`;
}

// Función para redirigir a productos con filtros
function redirectToProductos(filtro) {
    let url = 'productos.php';
    
    switch(filtro) {
        case 'stock':
            // Productos con stock > 0
            url += '?stock_filter=stock';
            break;
        case 'bajo':
            // Stock bajo (menos de 15)
            url += '?stock_filter=bajo';
            break;
        case 'agotado':
            // Sin stock
            url += '?stock_filter=agotado';
            break;
        default:
            // Total productos (sin filtros)
            break;
    }
    
    window.location.href = url;
}

// Función para mostrar tooltip en las barras del gráfico
function showChartTooltip(event, dia, transacciones, monto) {
    // Crear tooltip dinámico si no existe
    let tooltip = document.getElementById('chart-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'chart-tooltip';
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            pointer-events: none;
            z-index: 1000;
            border: 1px solid rgba(255,255,255,0.2);
        `;
        document.body.appendChild(tooltip);
    }
    
    tooltip.innerHTML = `
        <strong>${dia}</strong><br>
        Transacciones: ${transacciones}<br>
        Monto: $${monto.toLocaleString('es-ES')}
    `;
    
    tooltip.style.left = event.pageX + 10 + 'px';
    tooltip.style.top = event.pageY - 10 + 'px';
    tooltip.style.display = 'block';
}

function hideChartTooltip() {
    const tooltip = document.getElementById('chart-tooltip');
    if (tooltip) {
        tooltip.style.display = 'none';
    }
}

// Añadir eventos a las barras del gráfico después de cargar la página
document.addEventListener('DOMContentLoaded', function() {
    const chartBars = document.querySelectorAll('.chart-bar');
    chartBars.forEach(bar => {
        bar.addEventListener('mouseenter', function(e) {
            const title = this.getAttribute('title');
            if (title) {
                const parts = title.split(': ');
                if (parts.length >= 2) {
                    const dia = parts[0];
                    const info = parts[1];
                    // Extraer números del string
                    const transacciones = info.match(/(\d+) transacciones/);
                    const monto = info.match(/\$([0-9.,]+)/);
                    
                    if (transacciones && monto) {
                        showChartTooltip(e, dia, transacciones[1], parseFloat(monto[1].replace(/[.,]/g, '')));
                    }
                }
            }
        });
        
        bar.addEventListener('mouseleave', hideChartTooltip);
        
        bar.addEventListener('mousemove', function(e) {
            const tooltip = document.getElementById('chart-tooltip');
            if (tooltip && tooltip.style.display === 'block') {
                tooltip.style.left = e.pageX + 10 + 'px';
                tooltip.style.top = e.pageY - 10 + 'px';
            }
        });
    });
});
</script>
