<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';

// Obtener estadísticas del sistema
function obtenerEstadisticas($conn) {
    $stats = [];
    
    // Total ventas del día
    $query = "SELECT COALESCE(SUM(total), 0) as total_hoy FROM ventas WHERE DATE(fecha) = CURDATE()";
    $result = $conn->query($query);
    $stats['ventas_hoy'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total_hoy'] : 0;
    
    // Total ventas de ayer
    $query = "SELECT COALESCE(SUM(total), 0) as total_ayer FROM ventas WHERE DATE(fecha) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $result = $conn->query($query);
    $stats['ventas_ayer'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total_ayer'] : 0;
    
    // Calcular porcentaje de cambio
    if ($stats['ventas_ayer'] > 0) {
        $cambio = (($stats['ventas_hoy'] - $stats['ventas_ayer']) / $stats['ventas_ayer']) * 100;
        $stats['cambio_ventas'] = round($cambio, 1);
    } else {
        $stats['cambio_ventas'] = $stats['ventas_hoy'] > 0 ? 100 : 0;
    }
    
    // Vendedores activos
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol IN ('vendedor', 'jefe')";
    $result = $conn->query($query);
    $stats['vendedores'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total'] : 0;
    
    // Productos en stock
    $query = "SELECT COUNT(*) as total FROM productos WHERE stock > 0";
    $result = $conn->query($query);
    $stats['productos_stock'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total'] : 0;
    
    // Transacciones del día
    $query = "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = CURDATE()";
    $result = $conn->query($query);
    $stats['transacciones_hoy'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total'] : 0;
    
    // Productos con stock bajo (<=5 pero >0)
    $query = "SELECT COUNT(*) as total FROM productos WHERE stock <= 5 AND stock > 0";
    $result = $conn->query($query);
    $stats['stock_bajo'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total'] : 0;
    
    // Productos sin stock (=0)
    $query = "SELECT COUNT(*) as total FROM productos WHERE stock = 0";
    $result = $conn->query($query);
    $stats['sin_stock'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total'] : 0;
    
    // Total productos
    $query = "SELECT COUNT(*) as total FROM productos";
    $result = $conn->query($query);
    $stats['total_productos'] = $result ? $result->fetch(PDO::FETCH_ASSOC)['total'] : 0;
    
    return $stats;
}

// Obtener datos para gráficas
function obtenerDatosGraficas($conn) {
    $datos = [];
    
    // Ventas últimos 7 días
    $query = "SELECT DATE(fecha) as fecha, SUM(total) as total 
              FROM ventas 
              WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
              GROUP BY DATE(fecha) 
              ORDER BY fecha";
    $result = $conn->query($query);
    $datos['ventas_semana'] = [];
    if ($result) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $datos['ventas_semana'][] = $row;
        }
    }
    
    // Top productos más vendidos
    $query = "SELECT p.nombre, SUM(vd.cantidad) as cantidad_vendida
              FROM productos p
              JOIN venta_detalles vd ON p.id = vd.producto_id
              JOIN ventas v ON vd.venta_id = v.id
              WHERE v.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY p.id, p.nombre
              ORDER BY cantidad_vendida DESC
              LIMIT 5";
    $result = $conn->query($query);
    $datos['top_productos'] = [];
    if ($result) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $datos['top_productos'][] = $row;
        }
    }
    
    return $datos;
}

$estadisticas = obtenerEstadisticas($conn);
$datosGraficas = obtenerDatosGraficas($conn);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
/* Dashboard moderno y minimalista */
.dashboard-container {
    padding: 0;
    background: transparent;
}

.dashboard-header {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
}

.welcome-title {
    color: var(--text-primary);
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.welcome-subtitle {
    color: var(--text-secondary);
    font-size: 1.125rem;
    margin-bottom: 1rem;
    font-weight: 400;
}

.current-time {
    color: var(--text-muted);
    font-size: 0.875rem;
    font-weight: 500;
}

/* Grid de estadísticas minimalista */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--border-light);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.stat-icon.sales { 
    background: var(--accent-success);
    color: white;
}
.stat-icon.users { 
    background: var(--accent-primary);
    color: white;
}
.stat-icon.products { 
    background: var(--accent-warning);
    color: white;
}
.stat-icon.transactions { 
    background: var(--accent-secondary);
    color: white;
}
.stat-icon.alert { 
    background: var(--accent-warning);
    color: white;
}
.stat-icon.critical { 
    background: var(--accent-danger);
    color: white;
}
.stat-icon.inventory { 
    background: var(--accent-primary);
    color: white;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    display: block;
    line-height: 1.1;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.stat-change {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.stat-change.positive {
    background: rgba(16, 185, 129, 0.1);
    color: var(--accent-success);
}

.stat-change.negative {
    background: rgba(239, 68, 68, 0.1);
    color: var(--accent-danger);
}

.stat-change.neutral {
    background: rgba(156, 163, 175, 0.1);
    color: var(--text-muted);
}

/* Grid de contenido principal */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

/* Tarjetas de gráficas minimalistas */
.chart-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.chart-title {
    color: var(--text-primary);
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 1rem;
}

/* Acciones rápidas minimalistas */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.action-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-decoration: none;
    color: inherit;
    transition: var(--transition);
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--accent-primary);
    text-decoration: none;
    color: inherit;
}

.action-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--accent-primary);
    transition: var(--transition);
}

.action-card:hover .action-icon {
    color: var(--accent-primary);
    transform: scale(1.1);
}

.action-title {
    color: var(--text-primary);
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.5rem;
}

.action-desc {
    color: var(--text-secondary);
    font-size: 0.875rem;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-header {
        padding: 1.5rem;
    }
    
    .welcome-title {
        font-size: 2rem;
    }
    
    .chart-container {
        height: 250px;
    }
    
    .quick-actions {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}

/* Animaciones suaves */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-up {
    animation: fadeInUp 0.6s ease-out forwards;
}

/* Notificaciones minimalistas y modernas */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 320px;
    max-width: 420px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: translateX(100%);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 0;
    animation: slideInNotification 0.3s ease-out forwards;
    backdrop-filter: blur(20px);
}

@keyframes slideInNotification {
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification.info {
    border-left: 4px solid var(--accent-primary);
}

.notification.warning {
    border-left: 4px solid var(--accent-warning);
}

.notification.success {
    border-left: 4px solid var(--accent-success);
}

.notification.error {
    border-left: 4px solid var(--accent-danger);
}

.notification-content {
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.notification-message {
    flex: 1;
    color: var(--text-primary);
    font-size: 0.875rem;
    line-height: 1.5;
    font-weight: 500;
}

.notification-close {
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.notification-close:hover {
    color: var(--text-primary);
    background: var(--bg-tertiary);
}

/* Estados de carga para gráficas */
.chart-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;
    color: var(--text-secondary);
    font-size: 0.875rem;
    flex-direction: column;
    gap: 1rem;
}

.chart-loading::before {
    content: '';
    width: 32px;
    height: 32px;
    border: 3px solid var(--border-color);
    border-top-color: var(--accent-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Efectos hover mejorados */
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover::before {
    opacity: 1;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--accent-primary);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.action-card:hover::before {
    transform: scaleX(1);
}

/* Mejoras responsive para notificaciones */
@media (max-width: 768px) {
    .notification {
        min-width: calc(100vw - 40px);
        max-width: calc(100vw - 40px);
        right: 20px;
        left: 20px;
        transform: translateY(-100%);
    }
    
    @keyframes slideInNotification {
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .notification-content {
        padding: 1rem;
    }
}
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

/* Loading spinner minimalista */
.loading-spinner {
    display: inline-block;
    width: 32px;
    height: 32px;
    border: 2px solid var(--border-color);
    border-radius: 50%;
    border-top-color: var(--accent-primary);
    animation: spin 1s linear infinite;
}

/* Mejoras para charts en modo claro/oscuro */
.chart-container canvas {
    filter: none;
    transition: var(--transition);
}

[data-theme="light"] .chart-container canvas {
    /* Ajustes específicos para modo claro si son necesarios */
}
</style>

<div class="dashboard-container">
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <h1 class="welcome-title">Panel de Control Administrativo</h1>
        <p class="welcome-subtitle">¡Bienvenido, <?= htmlspecialchars($_SESSION['user_nombre'] ?? 'Administrador') ?>!</p>
        <p class="current-time" id="currentTime"></p>
    </div>

    <!-- Grid de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card animate-up" style="animation-delay: 0.1s">
            <div class="stat-icon sales">💰</div>
            <span class="stat-value">$<?= number_format($estadisticas['ventas_hoy'], 0, ',', '.') ?></span>
            <div class="stat-label">Ventas de Hoy</div>
            <?php 
            $cambio = $estadisticas['cambio_ventas'];
            if ($cambio > 0): ?>
                <div class="stat-change positive">+<?= $cambio ?>% vs ayer</div>
            <?php elseif ($cambio < 0): ?>
                <div class="stat-change negative"><?= $cambio ?>% vs ayer</div>
            <?php else: ?>
                <div class="stat-change neutral">Sin cambio vs ayer</div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card animate-up" style="animation-delay: 0.2s">
            <div class="stat-icon users">👥</div>
            <span class="stat-value"><?= $estadisticas['vendedores'] ?></span>
            <div class="stat-label">Vendedores Activos</div>
        </div>
        
        <div class="stat-card animate-up" style="animation-delay: 0.3s">
            <div class="stat-icon products">📦</div>
            <span class="stat-value"><?= $estadisticas['productos_stock'] ?></span>
            <div class="stat-label">Productos en Stock</div>
            <div class="stat-change negative">de <?= $estadisticas['total_productos'] ?> total</div>

        </div>
        
        <div class="stat-card animate-up" style="animation-delay: 0.4s">
            <div class="stat-icon transactions">📊</div>
            <span class="stat-value"><?= $estadisticas['transacciones_hoy'] ?></span>
            <div class="stat-label">Transacciones Hoy</div>
        </div>
        
        <div class="stat-card animate-up" style="animation-delay: 0.5s">
            <div class="stat-icon alert">⚠️</div>
            <span class="stat-value"><?= $estadisticas['stock_bajo'] ?></span>
            <div class="stat-label">Stock Bajo (≤5)</div>
            <?php if ($estadisticas['stock_bajo'] > 0): ?>
            <div class="stat-change negative">Requiere reposición</div>
            <?php else: ?>
            <div class="stat-change positive">Stock controlado</div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card animate-up" style="animation-delay: 0.6s">
            <div class="stat-icon critical">🚫</div>
            <span class="stat-value"><?= $estadisticas['sin_stock'] ?></span>
            <div class="stat-label">Sin Stock</div>
            <?php if ($estadisticas['sin_stock'] > 0): ?>
            <div class="stat-change negative">Atención urgente</div>
            <?php else: ?>
            <div class="stat-change positive">Todo disponible</div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card animate-up" style="animation-delay: 0.7s">
            <div class="stat-icon inventory">📋</div>
            <span class="stat-value"><?= $estadisticas['total_productos'] ?></span>
            <div class="stat-label">Total Productos</div>
            <div class="stat-change neutral"><?= $estadisticas['productos_stock'] ?> disponibles</div>
        </div>
    </div>

    <!-- Grid de contenido principal -->
    <div class="content-grid">
        <!-- Gráfica de ventas -->
        <div class="chart-card animate-up" style="animation-delay: 0.8s">
            <h3 class="chart-title">
                <i class="bi bi-graph-up"></i>
                Ventas de los Últimos 7 Días
            </h3>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Top productos -->
        <div class="chart-card animate-up" style="animation-delay: 0.9s">
            <h3 class="chart-title">
                <i class="bi bi-award"></i>
                Productos Más Vendidos
            </h3>
            <div class="chart-container">
                <canvas id="productsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="quick-actions">
        <a href="productos.php" class="action-card animate-up" style="animation-delay: 1.0s">
            <div class="action-icon"><i class="bi bi-box-seam"></i></div>
            <div class="action-title">Gestión de Productos</div>
            <div class="action-desc">Administrar inventario y catálogo</div>
        </a>
        
        <a href="ventas.php" class="action-card animate-up" style="animation-delay: 1.1s">
            <div class="action-icon"><i class="bi bi-receipt"></i></div>
            <div class="action-title">Registro de Ventas</div>
            <div class="action-desc">Consultar todas las transacciones</div>
        </a>
        
        <a href="informes.php" class="action-card animate-up" style="animation-delay: 1.2s">
            <div class="action-icon"><i class="bi bi-graph-up"></i></div>
            <div class="action-title">Informes Detallados</div>
            <div class="action-desc">Análisis y reportes avanzados</div>
        </a>
        
        <a href="vendedores.php" class="action-card animate-up" style="animation-delay: 1.3s">
            <div class="action-icon"><i class="bi bi-people"></i></div>
            <div class="action-title">Gestión de Personal</div>
            <div class="action-desc">Administrar vendedores y permisos</div>
        </a>
        
        <a href="apertura_cierre.php" class="action-card animate-up" style="animation-delay: 1.4s">
            <div class="action-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="action-title">Control de Caja</div>
            <div class="action-desc">Apertura y cierre diario</div>
        </a>
        
        <a href="seguridad.php" class="action-card animate-up" style="animation-delay: 1.5s">
            <div class="action-icon"><i class="bi bi-shield-check"></i></div>
            <div class="action-title">Seguridad</div>
            <div class="action-desc">Configuración y auditoría</div>
        </a>
    </div>
</div>

<script>
// Configuración dinámica de Chart.js según el tema
function updateChartDefaults() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    Chart.defaults.color = isDark ? 'rgba(203, 213, 225, 0.8)' : 'rgba(51, 65, 85, 0.8)';
    Chart.defaults.borderColor = isDark ? 'rgba(51, 65, 85, 0.2)' : 'rgba(226, 232, 240, 0.5)';
    Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, sans-serif';
}

// Función para actualizar la hora
function updateTime() {
    const now = new Date();
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    document.getElementById('currentTime').textContent = now.toLocaleDateString('es-ES', options);
}

// Datos para las gráficas desde PHP
const ventasData = <?= json_encode($datosGraficas['ventas_semana']) ?>;
const productosData = <?= json_encode($datosGraficas['top_productos']) ?>;

// Variables para almacenar las instancias de los charts
let salesChart = null;
let productsChart = null;

// Configuración de la gráfica de ventas
function initSalesChart() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    
    // Destruir chart existente si existe
    if (salesChart) {
        salesChart.destroy();
    }
    
    // Preparar datos de los últimos 7 días
    const fechas = [];
    const ventas = [];
    const hoy = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const fecha = new Date(hoy);
        fecha.setDate(fecha.getDate() - i);
        const fechaStr = fecha.toISOString().split('T')[0];
        fechas.push(fecha.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' }));
        
        const ventaDelDia = ventasData.find(v => v.fecha === fechaStr);
        ventas.push(ventaDelDia ? parseFloat(ventaDelDia.total) : 0);
    }
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                label: 'Ventas ($)',
                data: ventas,
                borderColor: '#3b82f6',
                backgroundColor: isDark ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.05)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: isDark ? '#ffffff' : '#0f172a',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: isDark ? 'rgba(30, 41, 59, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                    titleColor: isDark ? '#f8fafc' : '#0f172a',
                    bodyColor: isDark ? '#cbd5e1' : '#334155',
                    borderColor: isDark ? '#475569' : '#e2e8f0',
                    borderWidth: 1,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: isDark ? 'rgba(51, 65, 85, 0.3)' : 'rgba(226, 232, 240, 0.7)',
                        borderDash: [2, 4]
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        },
                        color: isDark ? 'rgba(203, 213, 225, 0.8)' : 'rgba(51, 65, 85, 0.8)'
                    }
                },
                x: {
                    grid: {
                        color: isDark ? 'rgba(51, 65, 85, 0.3)' : 'rgba(226, 232, 240, 0.7)',
                        borderDash: [2, 4]
                    },
                    ticks: {
                        color: isDark ? 'rgba(203, 213, 225, 0.8)' : 'rgba(51, 65, 85, 0.8)'
                    }
                }
            }
        }
    });
}

// Configuración de la gráfica de productos
function initProductsChart() {
    const ctx = document.getElementById('productsChart').getContext('2d');
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    
    // Destruir chart existente si existe
    if (productsChart) {
        productsChart.destroy();
    }
    
    const labels = productosData.map(p => p.nombre);
    const data = productosData.map(p => parseInt(p.cantidad_vendida));
    
    const colors = [
        '#3b82f6', // blue
        '#10b981', // emerald
        '#f59e0b', // amber
        '#8b5cf6', // violet
        '#ef4444'  // red
    ];
    
    productsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.length > 0 ? labels : ['Sin datos'],
            datasets: [{
                data: data.length > 0 ? data : [1],
                backgroundColor: data.length > 0 ? colors.slice(0, data.length) : [isDark ? 'rgba(100, 116, 139, 0.3)' : 'rgba(226, 232, 240, 0.5)'],
                borderColor: isDark ? 'rgba(51, 65, 85, 0.5)' : 'rgba(255, 255, 255, 0.8)',
                borderWidth: 2,
                hoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        },
                        color: isDark ? 'rgba(203, 213, 225, 0.8)' : 'rgba(51, 65, 85, 0.8)'
                    }
                },
                tooltip: {
                    backgroundColor: isDark ? 'rgba(30, 41, 59, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                    titleColor: isDark ? '#f8fafc' : '#0f172a',
                    bodyColor: isDark ? '#cbd5e1' : '#334155',
                    borderColor: isDark ? '#475569' : '#e2e8f0',
                    borderWidth: 1,
                    cornerRadius: 8
                }
            },
            cutout: '60%'
        }
    });
}

// Función para reinicializar charts cuando cambia el tema
function reinitCharts() {
    updateChartDefaults();
    setTimeout(() => {
        initSalesChart();
        initProductsChart();
    }, 100);
}

// Animaciones de entrada
function animateElements() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    document.querySelectorAll('.animate-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        observer.observe(el);
    });
}

// Actualizar estadísticas cada 30 segundos
function refreshStats() {
    // Comentado temporalmente hasta implementar dashboard_ajax.php
    /*
    fetch('dashboard_ajax.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            // Actualizar los valores en las tarjetas si es necesario
        })
        .catch(error => console.log('Error actualizando estadísticas:', error));
    */
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    updateTime();
    setInterval(updateTime, 1000);
    
    // Inicializar charts
    setTimeout(() => {
        updateChartDefaults();
        initSalesChart();
        initProductsChart();
        animateElements();
    }, 100);
    
    // Actualizar estadísticas cada 30 segundos (comentado hasta implementar AJAX)
    // setInterval(refreshStats, 30000);
    
    // Efectos hover suaves para las tarjetas
    document.querySelectorAll('.stat-card, .action-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Listener para cambios de tema
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                reinitCharts();
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-theme']
    });
});

// Función para mostrar notificaciones minimalistas
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Manual close
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    });
}

// Verificar alertas de stock
<?php if ($estadisticas['sin_stock'] > 0): ?>
setTimeout(() => {
    showNotification(`🚫 CRÍTICO: Hay <?= $estadisticas['sin_stock'] ?> productos SIN STOCK que necesitan reposición inmediata`, 'error');
}, 1500);
<?php endif; ?>

<?php if ($estadisticas['stock_bajo'] > 0): ?>
setTimeout(() => {
    showNotification(`⚠️ Hay <?= $estadisticas['stock_bajo'] ?> productos con stock bajo que requieren atención`, 'warning');
}, 2500);
<?php endif; ?>
</script>


