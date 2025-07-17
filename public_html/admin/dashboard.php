<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';

// Obtener estadísticas del sistema
function obtenerEstadisticas($conn) {
    $stats = [];
    
    // Total ventas del día
    $query = "SELECT COALESCE(SUM(total), 0) as total_hoy FROM ventas WHERE DATE(fecha) = CURDATE()";
    $result = $conn->query($query);
    $stats['ventas_hoy'] = $result ? $result->fetch_assoc()['total_hoy'] : 0;
    
    // Vendedores activos
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol IN ('vendedor', 'jefe')";
    $result = $conn->query($query);
    $stats['vendedores'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Productos en stock
    $query = "SELECT COUNT(*) as total FROM productos WHERE stock > 0";
    $result = $conn->query($query);
    $stats['productos_stock'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Transacciones del día
    $query = "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = CURDATE()";
    $result = $conn->query($query);
    $stats['transacciones_hoy'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Productos con stock bajo
    $query = "SELECT COUNT(*) as total FROM productos WHERE stock <= 5 AND stock > 0";
    $result = $conn->query($query);
    $stats['stock_bajo'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Total productos
    $query = "SELECT COUNT(*) as total FROM productos";
    $result = $conn->query($query);
    $stats['total_productos'] = $result ? $result->fetch_assoc()['total'] : 0;
    
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
        while ($row = $result->fetch_assoc()) {
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
        while ($row = $result->fetch_assoc()) {
            $datos['top_productos'][] = $row;
        }
    }
    
    return $datos;
}

$estadisticas = obtenerEstadisticas($conn);
$datosGraficas = obtenerDatosGraficas($conn);
?>

<link href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
/* Estilos mejorados para el dashboard admin */
.dashboard-container {
    padding: 0;
    background: transparent;
}

.dashboard-header {
    background: linear-gradient(135deg, rgba(45, 27, 105, 0.1), rgba(17, 153, 142, 0.1));
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.welcome-title {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 300;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #ff6b6b, #feca57, #48dbfb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.welcome-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.current-time {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.95rem;
}

/* Grid de estadísticas */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.8), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #ff6b6b, #feca57, #48dbfb);
    border-radius: 16px 16px 0 0;
}

.stat-card:hover {
    transform: translateY(-8px) scale(1.02);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.stat-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1rem;
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
}

.stat-icon.sales { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
.stat-icon.users { background: linear-gradient(135deg, #48dbfb, #74edf7); }
.stat-icon.products { background: linear-gradient(135deg, #feca57, #ffd93d); }
.stat-icon.transactions { background: linear-gradient(135deg, #ff9ff3, #f368e0); }
.stat-icon.alert { background: linear-gradient(135deg, #ff7675, #fd79a8); }
.stat-icon.inventory { background: linear-gradient(135deg, #6c5ce7, #a29bfe); }

.stat-value {
    font-size: 2.2rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.5rem;
    display: block;
}

.stat-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    font-weight: 500;
}

.stat-change {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    display: inline-block;
}

.stat-change.positive {
    background: rgba(0, 255, 127, 0.1);
    color: #00ff7f;
}

.stat-change.negative {
    background: rgba(255, 107, 107, 0.1);
    color: #ff6b6b;
}

/* Grid de contenido principal */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

/* Tarjetas de gráficas */
.chart-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    backdrop-filter: blur(15px);
}

.chart-title {
    color: #ffffff;
    font-size: 1.3rem;
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

/* Acciones rápidas mejoradas */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.action-card {
    background: linear-gradient(135deg, rgba(25, 25, 35, 0.9), rgba(45, 27, 105, 0.2));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    text-align: center;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.action-card:hover {
    transform: translateY(-8px) scale(1.02);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    text-decoration: none;
    color: inherit;
}

.action-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #48dbfb;
}

.action-title {
    color: #ffffff;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.action-desc {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
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
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-up {
    animation: fadeInUp 0.6s ease-out forwards;
}

/* Loading spinner para las gráficas */
.loading-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 3px solid rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    border-top-color: #48dbfb;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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
            <div class="stat-change positive">+12% vs ayer</div>
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
            <div class="stat-label">Productos Stock Bajo</div>
            <?php if ($estadisticas['stock_bajo'] > 0): ?>
            <div class="stat-change negative">Requiere atención</div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card animate-up" style="animation-delay: 0.6s">
            <div class="stat-icon inventory">📋</div>
            <span class="stat-value"><?= $estadisticas['total_productos'] ?></span>
            <div class="stat-label">Total Productos</div>
        </div>
    </div>

    <!-- Grid de contenido principal -->
    <div class="content-grid">
        <!-- Gráfica de ventas -->
        <div class="chart-card animate-up" style="animation-delay: 0.7s">
            <h3 class="chart-title">
                <i class="bi bi-graph-up"></i>
                Ventas de los Últimos 7 Días
            </h3>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Top productos -->
        <div class="chart-card animate-up" style="animation-delay: 0.8s">
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
        <a href="productos.php" class="action-card animate-up" style="animation-delay: 0.9s">
            <div class="action-icon"><i class="bi bi-box-seam"></i></div>
            <div class="action-title">Gestión de Productos</div>
            <div class="action-desc">Administrar inventario y catálogo</div>
        </a>
        
        <a href="ventas.php" class="action-card animate-up" style="animation-delay: 1s">
            <div class="action-icon"><i class="bi bi-receipt"></i></div>
            <div class="action-title">Registro de Ventas</div>
            <div class="action-desc">Consultar todas las transacciones</div>
        </a>
        
        <a href="informes.php" class="action-card animate-up" style="animation-delay: 1.1s">
            <div class="action-icon"><i class="bi bi-graph-up"></i></div>
            <div class="action-title">Informes Detallados</div>
            <div class="action-desc">Análisis y reportes avanzados</div>
        </a>
        
        <a href="vendedores.php" class="action-card animate-up" style="animation-delay: 1.2s">
            <div class="action-icon"><i class="bi bi-people"></i></div>
            <div class="action-title">Gestión de Personal</div>
            <div class="action-desc">Administrar vendedores y permisos</div>
        </a>
        
        <a href="apertura_cierre.php" class="action-card animate-up" style="animation-delay: 1.3s">
            <div class="action-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="action-title">Control de Caja</div>
            <div class="action-desc">Apertura y cierre diario</div>
        </a>
        
        <a href="seguridad.php" class="action-card animate-up" style="animation-delay: 1.4s">
            <div class="action-icon"><i class="bi bi-shield-check"></i></div>
            <div class="action-title">Seguridad</div>
            <div class="action-desc">Configuración y auditoría</div>
        </a>
    </div>
</div>

<script>
// Configuración global de Chart.js
Chart.defaults.color = 'rgba(255, 255, 255, 0.8)';
Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, sans-serif';

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

// Configuración de la gráfica de ventas
function initSalesChart() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Preparar datos de los últimos 7 días
    const fechas = [];
    const ventas = [];
    const hoy = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const fecha = new Date(hoy);
        fecha.setDate(fecha.getDate() - i);
        const fechaStr = fecha.toISOString().split('T')[0];
        fechas.push(fecha.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' }));
        
        // Buscar si hay ventas para esta fecha
        const ventaDelDia = ventasData.find(v => v.fecha === fechaStr);
        ventas.push(ventaDelDia ? parseFloat(ventaDelDia.total) : 0);
    }
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: fechas,
            datasets: [{
                label: 'Ventas ($)',
                data: ventas,
                borderColor: '#48dbfb',
                backgroundColor: 'rgba(72, 219, 251, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#48dbfb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#feca57'
                }
            }
        }
    });
}

// Configuración de la gráfica de productos
function initProductsChart() {
    const ctx = document.getElementById('productsChart').getContext('2d');
    
    const labels = productosData.map(p => p.nombre);
    const data = productosData.map(p => parseInt(p.cantidad_vendida));
    
    const colors = [
        '#ff6b6b',
        '#feca57',
        '#48dbfb',
        '#ff9ff3',
        '#54a0ff'
    ];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels.length > 0 ? labels : ['Sin datos'],
            datasets: [{
                data: data.length > 0 ? data : [1],
                backgroundColor: data.length > 0 ? colors.slice(0, data.length) : ['rgba(255, 255, 255, 0.1)'],
                borderColor: 'rgba(255, 255, 255, 0.2)',
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
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
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
        el.style.transform = 'translateY(30px)';
        observer.observe(el);
    });
}

// Actualizar estadísticas cada 30 segundos
function refreshStats() {
    // Aquí podrías hacer una llamada AJAX para actualizar las estadísticas
    // sin recargar la página completa
    fetch('dashboard_ajax.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            // Actualizar los valores en las tarjetas
            // Implementar según necesidades específicas
        })
        .catch(error => console.log('Error actualizando estadísticas:', error));
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    updateTime();
    setInterval(updateTime, 1000);
    
    // Delay para permitir que el CSS se cargue completamente
    setTimeout(() => {
        initSalesChart();
        initProductsChart();
        animateElements();
    }, 100);
    
    // Actualizar estadísticas cada 30 segundos
    setInterval(refreshStats, 30000);
    
    // Agregar efectos hover a las tarjetas de stats
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Auto-trigger transition if coming from login
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from') === 'login') {
        setTimeout(() => {
            if (typeof PageTransitions !== 'undefined') {
                const pageTransitions = new PageTransitions();
                pageTransitions.adminTransition();
            }
        }, 100);
    }
});

// Función para mostrar notificaciones
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
        notification.remove();
    }, 5000);
    
    // Manual close
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.remove();
    });
}

// Verificar alertas de stock bajo
<?php if ($estadisticas['stock_bajo'] > 0): ?>
setTimeout(() => {
    showNotification(`⚠️ Hay <?= $estadisticas['stock_bajo'] ?> productos con stock bajo que requieren atención`, 'warning');
}, 2000);
<?php endif; ?>
</script>

<style>
/* Estilos para notificaciones */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.95), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 1rem;
    z-index: 1000;
    backdrop-filter: blur(15px);
    color: white;
    max-width: 300px;
    animation: slideInRight 0.3s ease-out;
}

.notification.warning {
    border-left: 4px solid #feca57;
}

.notification.success {
    border-left: 4px solid #00ff7f;
}

.notification.error {
    border-left: 4px solid #ff6b6b;
}

.notification-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.notification-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    color: white;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>


