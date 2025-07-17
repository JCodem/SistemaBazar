<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';

// Obtener parámetros de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo'] ?? 'ventas';

// Función para obtener datos de ventas
function obtenerDatosVentas($conn, $fecha_inicio, $fecha_fin) {
    $datos = [];
    
    // Ventas totales por día
    $query = "SELECT DATE(fecha) as fecha, COUNT(*) as transacciones, SUM(total) as total_ventas
              FROM ventas 
              WHERE fecha BETWEEN ? AND ? 
              GROUP BY DATE(fecha) 
              ORDER BY fecha";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $datos['ventas_diarias'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Productos más vendidos
    $query = "SELECT p.nombre, SUM(vd.cantidad) as cantidad_vendida, SUM(vd.subtotal) as total_vendido
              FROM productos p
              JOIN venta_detalles vd ON p.id = vd.producto_id
              JOIN ventas v ON vd.venta_id = v.id
              WHERE v.fecha BETWEEN ? AND ?
              GROUP BY p.id, p.nombre
              ORDER BY cantidad_vendida DESC
              LIMIT 10";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $datos['productos_vendidos'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Ventas por vendedor
    $query = "SELECT u.nombre, COUNT(v.id) as num_ventas, SUM(v.total) as total_vendido
              FROM usuarios u
              JOIN ventas v ON u.id = v.usuario_id
              WHERE v.fecha BETWEEN ? AND ?
              GROUP BY u.id, u.nombre
              ORDER BY total_vendido DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $datos['ventas_vendedor'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Resumen general
    $query = "SELECT 
                COUNT(*) as total_transacciones,
                SUM(total) as total_ventas,
                AVG(total) as promedio_venta,
                MIN(total) as venta_minima,
                MAX(total) as venta_maxima
              FROM ventas 
              WHERE fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $datos['resumen'] = $stmt->get_result()->fetch_assoc();
    
    return $datos;
}

// Función para obtener datos de inventario
function obtenerDatosInventario($conn) {
    $datos = [];
    
    // Stock total por categorías
    $query = "SELECT 
                COUNT(*) as total_productos,
                SUM(stock) as stock_total,
                SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as productos_stock_bajo,
                SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as productos_sin_stock,
                SUM(precio * stock) as valor_inventario
              FROM productos";
    $result = $conn->query($query);
    $datos['resumen_inventario'] = $result->fetch_assoc();
    
    // Productos con stock bajo
    $query = "SELECT nombre, stock, precio, (precio * stock) as valor_stock
              FROM productos 
              WHERE stock <= 5 
              ORDER BY stock ASC";
    $result = $conn->query($query);
    $datos['stock_bajo'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Distribución de stock
    $query = "SELECT 
                CASE 
                    WHEN stock = 0 THEN 'Sin Stock'
                    WHEN stock <= 5 THEN 'Stock Bajo'
                    WHEN stock <= 20 THEN 'Stock Medio'
                    ELSE 'Stock Alto'
                END as categoria_stock,
                COUNT(*) as cantidad_productos
              FROM productos
              GROUP BY categoria_stock
              ORDER BY cantidad_productos DESC";
    $result = $conn->query($query);
    $datos['distribucion_stock'] = $result->fetch_all(MYSQLI_ASSOC);
    
    return $datos;
}

$datosReporte = [];
if ($tipo_reporte === 'ventas') {
    $datosReporte = obtenerDatosVentas($conn, $fecha_inicio, $fecha_fin);
} elseif ($tipo_reporte === 'inventario') {
    $datosReporte = obtenerDatosInventario($conn);
}
?>

<style>
.reportes-container {
    padding: 0;
}

.reportes-header {
    background: linear-gradient(135deg, rgba(45, 27, 105, 0.1), rgba(17, 153, 142, 0.1));
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.reportes-title {
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
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    font-weight: 500;
}

.filter-input, .filter-select {
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
    background: linear-gradient(135deg, #48dbfb, #0abde3);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 2rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    height: fit-content;
}

.btn-generate:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(72, 219, 251, 0.3);
}

.report-tabs {
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
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
    margin-bottom: 1rem;
}

.stat-icon.sales { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
.stat-icon.transactions { background: linear-gradient(135deg, #48dbfb, #74edf7); }
.stat-icon.average { background: linear-gradient(135deg, #feca57, #ffd93d); }
.stat-icon.inventory { background: linear-gradient(135deg, #6c5ce7, #a29bfe); }

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

.report-section {
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

.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 2rem;
}

.table-container {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(20, 20, 30, 0.8);
}

.report-table th {
    background: rgba(45, 27, 105, 0.5);
    color: #ffffff;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.report-table td {
    padding: 1rem;
    color: rgba(255, 255, 255, 0.9);
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.report-table tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.export-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn-export {
    background: linear-gradient(135deg, #6c5ce7, #a29bfe);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-export:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(108, 92, 231, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .report-tabs {
        flex-direction: column;
    }
    
    .tab-btn {
        text-align: left;
        padding: 0.75rem 1rem;
    }
    
    .export-actions {
        flex-direction: column;
    }
}
</style>

<div class="reportes-container">
    <!-- Header -->
    <div class="reportes-header">
        <h1 class="reportes-title">Informes y Reportes</h1>
        <p style="color: rgba(255, 255, 255, 0.7); margin: 0;">
            Análisis detallado de ventas, inventario y rendimiento del negocio
        </p>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
        <form method="GET" id="reportForm">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Tipo de Reporte</label>
                    <select name="tipo" class="filter-select" onchange="toggleDateFilters()">
                        <option value="ventas" <?= $tipo_reporte === 'ventas' ? 'selected' : '' ?>>Ventas</option>
                        <option value="inventario" <?= $tipo_reporte === 'inventario' ? 'selected' : '' ?>>Inventario</option>
                    </select>
                </div>
                
                <div class="filter-group" id="fechaInicio" style="<?= $tipo_reporte === 'inventario' ? 'display: none;' : '' ?>">
                    <label class="filter-label">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="filter-input" value="<?= $fecha_inicio ?>">
                </div>
                
                <div class="filter-group" id="fechaFin" style="<?= $tipo_reporte === 'inventario' ? 'display: none;' : '' ?>">
                    <label class="filter-label">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="filter-input" value="<?= $fecha_fin ?>">
                </div>
                
                <button type="submit" class="btn-generate">
                    <i class="bi bi-bar-chart-line"></i> Generar Reporte
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($datosReporte)): ?>
        
        <?php if ($tipo_reporte === 'ventas'): ?>
            <!-- Estadísticas de Ventas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon sales">💰</div>
                    <div class="stat-value">$<?= number_format($datosReporte['resumen']['total_ventas'] ?? 0, 0, ',', '.') ?></div>
                    <div class="stat-label">Total Ventas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon transactions">📊</div>
                    <div class="stat-value"><?= $datosReporte['resumen']['total_transacciones'] ?? 0 ?></div>
                    <div class="stat-label">Transacciones</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon average">📈</div>
                    <div class="stat-value">$<?= number_format($datosReporte['resumen']['promedio_venta'] ?? 0, 0, ',', '.') ?></div>
                    <div class="stat-label">Venta Promedio</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon sales">🎯</div>
                    <div class="stat-value">$<?= number_format($datosReporte['resumen']['venta_maxima'] ?? 0, 0, ',', '.') ?></div>
                    <div class="stat-label">Venta Máxima</div>
                </div>
            </div>

            <!-- Gráfica de Ventas Diarias -->
            <div class="report-section">
                <h3 class="section-title">
                    <i class="bi bi-graph-up"></i>
                    Ventas Diarias
                </h3>
                <div class="chart-container">
                    <canvas id="ventasDiariasChart"></canvas>
                </div>
            </div>

            <!-- Productos Más Vendidos -->
            <div class="report-section">
                <h3 class="section-title">
                    <i class="bi bi-award"></i>
                    Productos Más Vendidos
                </h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad Vendida</th>
                                <th>Total Vendido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datosReporte['productos_vendidos'] as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                <td><?= $producto['cantidad_vendida'] ?> unidades</td>
                                <td>$<?= number_format($producto['total_vendido'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ventas por Vendedor -->
            <?php if (!empty($datosReporte['ventas_vendedor'])): ?>
            <div class="report-section">
                <h3 class="section-title">
                    <i class="bi bi-people"></i>
                    Rendimiento por Vendedor
                </h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th>Número de Ventas</th>
                                <th>Total Vendido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datosReporte['ventas_vendedor'] as $vendedor): ?>
                            <tr>
                                <td><?= htmlspecialchars($vendedor['nombre']) ?></td>
                                <td><?= $vendedor['num_ventas'] ?></td>
                                <td>$<?= number_format($vendedor['total_vendido'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($tipo_reporte === 'inventario'): ?>
            <!-- Estadísticas de Inventario -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon inventory">📦</div>
                    <div class="stat-value"><?= $datosReporte['resumen_inventario']['total_productos'] ?></div>
                    <div class="stat-label">Total Productos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon inventory">📊</div>
                    <div class="stat-value"><?= number_format($datosReporte['resumen_inventario']['stock_total']) ?></div>
                    <div class="stat-label">Stock Total</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon sales">💰</div>
                    <div class="stat-value">$<?= number_format($datosReporte['resumen_inventario']['valor_inventario'], 0, ',', '.') ?></div>
                    <div class="stat-label">Valor Inventario</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon transactions">⚠️</div>
                    <div class="stat-value"><?= $datosReporte['resumen_inventario']['productos_stock_bajo'] ?></div>
                    <div class="stat-label">Stock Bajo</div>
                </div>
            </div>

            <!-- Distribución de Stock -->
            <div class="report-section">
                <h3 class="section-title">
                    <i class="bi bi-pie-chart"></i>
                    Distribución de Stock
                </h3>
                <div class="chart-container">
                    <canvas id="stockDistribucionChart"></canvas>
                </div>
            </div>

            <!-- Productos con Stock Bajo -->
            <?php if (!empty($datosReporte['stock_bajo'])): ?>
            <div class="report-section">
                <h3 class="section-title">
                    <i class="bi bi-exclamation-triangle"></i>
                    Productos con Stock Bajo
                </h3>
                <div class="table-container">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock Actual</th>
                                <th>Precio Unitario</th>
                                <th>Valor Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($datosReporte['stock_bajo'] as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                <td style="color: #ff6b6b; font-weight: 600;"><?= $producto['stock'] ?> unidades</td>
                                <td>$<?= number_format($producto['precio'], 0, ',', '.') ?></td>
                                <td>$<?= number_format($producto['valor_stock'], 0, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Botones de Exportación -->
        <div class="export-actions">
            <button class="btn-export" onclick="exportToPDF()">
                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
            </button>
            <button class="btn-export" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
            </button>
            <button class="btn-export" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Configuración global de Chart.js
Chart.defaults.color = 'rgba(255, 255, 255, 0.8)';
Chart.defaults.font.family = 'Inter, -apple-system, BlinkMacSystemFont, sans-serif';

function toggleDateFilters() {
    const tipo = document.querySelector('select[name="tipo"]').value;
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');
    
    if (tipo === 'inventario') {
        fechaInicio.style.display = 'none';
        fechaFin.style.display = 'none';
    } else {
        fechaInicio.style.display = 'block';
        fechaFin.style.display = 'block';
    }
}

<?php if ($tipo_reporte === 'ventas' && !empty($datosReporte['ventas_diarias'])): ?>
// Gráfica de ventas diarias
const ventasDiariasData = <?= json_encode($datosReporte['ventas_diarias']) ?>;
const ctx1 = document.getElementById('ventasDiariasChart').getContext('2d');

new Chart(ctx1, {
    type: 'line',
    data: {
        labels: ventasDiariasData.map(v => new Date(v.fecha).toLocaleDateString('es-ES')),
        datasets: [{
            label: 'Ventas ($)',
            data: ventasDiariasData.map(v => parseFloat(v.total_ventas)),
            borderColor: '#48dbfb',
            backgroundColor: 'rgba(72, 219, 251, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }, {
            label: 'Transacciones',
            data: ventasDiariasData.map(v => parseInt(v.transacciones)),
            borderColor: '#feca57',
            backgroundColor: 'rgba(254, 202, 87, 0.1)',
            borderWidth: 2,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false },
            },
            x: {
                grid: { color: 'rgba(255, 255, 255, 0.1)' }
            }
        }
    }
});
<?php endif; ?>

<?php if ($tipo_reporte === 'inventario' && !empty($datosReporte['distribucion_stock'])): ?>
// Gráfica de distribución de stock
const stockDistribucionData = <?= json_encode($datosReporte['distribucion_stock']) ?>;
const ctx2 = document.getElementById('stockDistribucionChart').getContext('2d');

new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: stockDistribucionData.map(s => s.categoria_stock),
        datasets: [{
            data: stockDistribucionData.map(s => parseInt(s.cantidad_productos)),
            backgroundColor: ['#ff6b6b', '#feca57', '#48dbfb', '#6c5ce7'],
            borderColor: 'rgba(255, 255, 255, 0.2)',
            borderWidth: 2
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
                    usePointStyle: true
                }
            }
        },
        cutout: '60%'
    }
});
<?php endif; ?>

function exportToPDF() {
    alert('Función de exportación PDF en desarrollo');
}

function exportToExcel() {
    alert('Función de exportación Excel en desarrollo');
}

// Auto-submit form when dates change
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Auto-submit after 1 second delay
            setTimeout(() => {
                document.getElementById('reportForm').submit();
            }, 1000);
        });
    });
});
</script>
