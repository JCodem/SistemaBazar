<?php
// Verificar autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ./login.php');
    exit;
}

// Incluir conexión a base de datos
require_once '../../includes/db.php';

$titulo = 'Inventario de Productos';
include '../../includes/layout_vendedor.php';

// Obtener filtros
$busqueda = $_GET['busqueda'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$stock_bajo = $_GET['stock_bajo'] ?? '';

// Construir consulta base - adaptada a la estructura actual de la tabla
$sql = "SELECT id, nombre, precio, stock, sku, codigo_barras";

// Verificar si existen las columnas opcionales
$checkColumns = $conn->query("SHOW COLUMNS FROM productos");
$existingColumns = [];
while ($row = $checkColumns->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

$hasCategoria = in_array('categoria', $existingColumns);
$hasDescripcion = in_array('descripcion', $existingColumns);
$hasFechaCreacion = in_array('fecha_creacion', $existingColumns);

if ($hasCategoria) {
    $sql .= ", categoria";
}
if ($hasDescripcion) {
    $sql .= ", descripcion";
}
if ($hasFechaCreacion) {
    $sql .= ", fecha_creacion";
}

$sql .= " FROM productos WHERE 1=1";

$conditions = [];
$params = [];
$types = '';

// Agregar filtros
if (!empty($busqueda)) {
    if ($hasDescripcion) {
        $conditions[] = "(nombre LIKE ? OR sku LIKE ? OR codigo_barras LIKE ? OR descripcion LIKE ?)";
        $busquedaParam = "%$busqueda%";
        $params = array_merge($params, [$busquedaParam, $busquedaParam, $busquedaParam, $busquedaParam]);
        $types .= 'ssss';
    } else {
        $conditions[] = "(nombre LIKE ? OR sku LIKE ? OR codigo_barras LIKE ?)";
        $busquedaParam = "%$busqueda%";
        $params = array_merge($params, [$busquedaParam, $busquedaParam, $busquedaParam]);
        $types .= 'sss';
    }
}

if (!empty($categoria) && $hasCategoria) {
    $conditions[] = "categoria = ?";
    $params[] = $categoria;
    $types .= 's';
}

if ($stock_bajo === '1') {
    $conditions[] = "stock <= 10"; // Consideramos stock bajo <= 10 unidades
}

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY nombre ASC";

try {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $productos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            throw new Exception("Error preparando la consulta: " . $conn->error);
        }
    } else {
        $result = $conn->query($sql);
        if ($result) {
            $productos = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            throw new Exception("Error ejecutando la consulta: " . $conn->error);
        }
    }
    
    // Obtener categorías para el filtro (solo si la columna existe)
    $categorias = [];
    if ($hasCategoria) {
        $resultCategorias = $conn->query("SELECT DISTINCT categoria FROM productos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria");
        if ($resultCategorias) {
            while ($row = $resultCategorias->fetch_assoc()) {
                $categorias[] = $row['categoria'];
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Error al obtener productos: " . $e->getMessage();
    $productos = [];
    $categorias = [];
}
?>

<style>
/* Estilo global para eliminar fondos blancos */
body, html {
    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f0f 100%) !important;
}

/* Eliminar TODOS los fondos blancos */
.bg-white, .bg-light, .table-responsive, .card-body {
    background: rgba(15, 15, 25, 0.8) !important;
}

/* Estilo minimalista matching con login y dashboard */
.main-content {
    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f0f 100%) !important;
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

/* Estrellas de fondo para inventario */
.inventory-stars {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
}

.inventory-star {
    position: absolute;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    animation: inventoryTwinkle 6s ease-in-out infinite;
    filter: blur(0.5px);
}

@keyframes inventoryTwinkle {
    0%, 100% {
        opacity: 0.2;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.1);
    }
}

/* Contenedor principal con z-index alto */
.container-fluid {
    position: relative;
    z-index: 10;
    color: var(--text-primary, #ffffff);
    background: transparent !important;
}

/* Forzar background en TODOS los elementos de Bootstrap */
.container-fluid .card,
.container-fluid .card-body,
.container-fluid .table-responsive,
.container-fluid .card .card-body.p-0 {
    background: rgba(15, 15, 25, 0.8) !important;
}

/* Asegurar que ningún elemento hijo tenga fondo blanco */
.container-fluid * {
    background-color: transparent !important;
}

/* Re-aplicar fondos específicos donde los necesitamos */
.container-fluid .card {
    background: rgba(15, 15, 25, 0.8) !important;
}

.container-fluid .card-header {
    background: rgba(25, 25, 35, 0.9) !important;
}

.container-fluid .table-responsive {
    background: rgba(15, 15, 25, 0.9) !important;
}

.container-fluid .table tbody tr {
    background: rgba(20, 20, 30, 0.8) !important;
}

/* Cards con glassmorfismo */
.card {
    background: rgba(15, 15, 25, 0.8) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    color: white !important;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    border-color: rgba(255, 255, 255, 0.2);
}

.card-header {
    background: rgba(25, 25, 35, 0.9) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: white !important;
}

.card-body {
    background: rgba(15, 15, 25, 0.8) !important;
    color: white !important;
}

/* Forzar estilos específicos para override de Bootstrap */
.card.border-primary,
.card.border-success,
.card.border-warning,
.card.border-info {
    background: rgba(15, 15, 25, 0.8) !important;
}

.card.border-primary .card-body,
.card.border-success .card-body,
.card.border-warning .card-body,
.card.border-info .card-body {
    background: transparent !important;
}

/* Botones estilizados */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.btn-primary {
    background: rgba(25, 25, 35, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-primary:hover {
    background: rgba(35, 35, 45, 0.9);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.btn-success {
    background: rgba(34, 197, 94, 0.8);
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-success:hover {
    background: rgba(34, 197, 94, 0.9);
    transform: translateY(-2px);
}

.btn-outline-primary {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #93c5fd;
}

.btn-outline-primary:hover {
    background: rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.5);
    color: white;
}

.btn-outline-secondary {
    background: rgba(156, 163, 175, 0.1);
    border: 1px solid rgba(156, 163, 175, 0.3);
    color: #d1d5db;
}

.btn-outline-secondary:hover {
    background: rgba(156, 163, 175, 0.2);
    color: white;
}

/* Inputs y selects */
.form-control, .form-select {
    background: rgba(25, 25, 35, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    color: white;
    transition: all 0.3s ease;
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-control:focus, .form-select:focus {
    background: rgba(30, 30, 40, 0.9);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
    outline: none;
    color: white;
}

.form-label {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

/* Tabla estilizada */
.table {
    background: transparent;
    color: white !important;
    border-radius: 8px;
    overflow: hidden;
}

.table-dark {
    background: rgba(15, 15, 25, 0.9) !important;
}

.table-striped > tbody > tr:nth-of-type(odd) > td {
    background: rgba(255, 255, 255, 0.05) !important;
}

.table-hover > tbody > tr:hover > td {
    background: rgba(255, 255, 255, 0.1) !important;
}

.table th, .table td {
    border-color: rgba(255, 255, 255, 0.1) !important;
    padding: 12px;
    color: white !important;
}

.table tbody tr {
    background: rgba(20, 20, 30, 0.8) !important;
}

.table tbody tr:hover {
    background: rgba(30, 30, 40, 0.9) !important;
}

/* Estilos específicos para elementos dentro de la tabla */
.table .text-muted {
    color: rgba(255, 255, 255, 0.6) !important;
}

.table code {
    background: rgba(255, 255, 255, 0.1) !important;
    color: #93c5fd !important;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.85em;
}

.table .badge {
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
    color: white !important;
}

.table .badge.bg-secondary {
    background: rgba(75, 85, 99, 0.8) !important;
    border: 1px solid rgba(156, 163, 175, 0.3);
}

.table .badge.bg-success {
    background: rgba(34, 197, 94, 0.8) !important;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.table .badge.bg-warning {
    background: rgba(245, 158, 11, 0.8) !important;
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #000 !important;
}

.table .badge.bg-danger {
    background: rgba(239, 68, 68, 0.8) !important;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* Card que contiene la tabla */
.table-responsive {
    background: rgba(15, 15, 25, 0.9) !important;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

/* Forzar fondo oscuro en el card-body */
.card-body {
    background: rgba(15, 15, 25, 0.9) !important;
}

/* Forzar fondo oscuro en toda la card */
.card {
    background: rgba(15, 15, 25, 0.8) !important;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    color: white !important;
}

/* Forzar estilos en elementos específicos del card-body */
.card .card-body.p-0 {
    background: rgba(15, 15, 25, 0.9) !important;
    padding: 0 !important;
}

/* Div que contiene "No se encontraron productos" */
.card-body .text-center {
    background: rgba(15, 15, 25, 0.9) !important;
    color: white !important;
}

/* Encabezado de la tabla principal */
.card .card-header.bg-light {
    background: rgba(25, 25, 35, 0.9) !important;
    color: white !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

/* Estado sin productos */
.text-center .text-muted {
    color: rgba(255, 255, 255, 0.7) !important;
}

.text-center .fs-1.text-muted {
    color: rgba(255, 255, 255, 0.4) !important;
}

/* Cards de estadísticas */
.border-primary {
    border-color: rgba(59, 130, 246, 0.5) !important;
    background: rgba(59, 130, 246, 0.1);
}

.border-success {
    border-color: rgba(34, 197, 94, 0.5) !important;
    background: rgba(34, 197, 94, 0.1);
}

.border-warning {
    border-color: rgba(245, 158, 11, 0.5) !important;
    background: rgba(245, 158, 11, 0.1);
}

.border-info {
    border-color: rgba(14, 165, 233, 0.5) !important;
    background: rgba(14, 165, 233, 0.1);
}

.text-primary { color: #93c5fd !important; }
.text-success { color: #86efac !important; }
.text-warning { color: #fcd34d !important; }
.text-info { color: #7dd3fc !important; }

/* Badges */
.badge {
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 6px;
}

/* Títulos */
h2, h4, h5 {
    color: white;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Alertas */
.alert {
    background: rgba(15, 15, 25, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.alert-danger {
    background: rgba(220, 38, 38, 0.2);
    border-color: rgba(220, 38, 38, 0.3);
    color: #fca5a5;
}

/* Modal */
.modal-content {
    background: rgba(15, 15, 25, 0.95);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: white;
}

.modal-header {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.btn-close {
    filter: invert(1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        border-radius: 8px;
    }
}

/* Animaciones de entrada */
.fade-in {
    animation: fadeInUp 0.6s ease-out;
}

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

/* Scroll personalizado */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>

<!-- Estrellas de fondo para inventario -->
<div class="inventory-stars" id="inventoryStars"></div>

<div class="container-fluid fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-box-seam"></i> Inventario de Productos</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download"></i> Exportar
            </button>
            <button class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <button class="btn btn-outline-secondary" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="busqueda" class="form-label">Buscar Producto</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" 
                               value="<?= htmlspecialchars($busqueda) ?>" 
                               placeholder="Nombre, SKU, código de barras...">
                    </div>
                    <div class="col-md-3">
                        <label for="categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="categoria" name="categoria" <?= !$hasCategoria ? 'disabled' : '' ?>>
                            <option value="">Todas las categorías</option>
                            <?php if ($hasCategoria): ?>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" 
                                            <?= $categoria === $cat ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Agregar columna categoria a la tabla</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="stock_bajo" class="form-label">Nivel de Stock</label>
                        <select class="form-select" id="stock_bajo" name="stock_bajo">
                            <option value="">Todos los niveles</option>
                            <option value="1" <?= $stock_bajo === '1' ? 'selected' : '' ?>>Stock bajo (≤10)</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12">
                        <a href="inventario.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Limpiar filtros
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <?php
    $totalProductos = count($productos);
    $totalStock = array_sum(array_column($productos, 'stock'));
    $stockBajo = array_filter($productos, function($p) { return $p['stock'] <= 10; });
    $totalStockBajo = count($stockBajo);
    $valorTotal = array_sum(array_map(function($p) { return $p['precio'] * $p['stock']; }, $productos));
    ?>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= $totalProductos ?></h5>
                    <p class="card-text">Productos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h5 class="card-title text-success"><?= number_format($totalStock) ?></h5>
                    <p class="card-text">Unidades en Stock</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= $totalStockBajo ?></h5>
                    <p class="card-text">Stock Bajo</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-info">
                <div class="card-body">
                    <h5 class="card-title text-info">$<?= number_format($valorTotal) ?></h5>
                    <p class="card-text">Valor Total</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header" style="background: rgba(25, 25, 35, 0.9) !important; color: white !important; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
            <h5 class="mb-0" style="color: white !important;">Lista de Productos (<?= $totalProductos ?> productos)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($productos)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1" style="color: rgba(255, 255, 255, 0.4) !important;"></i>
                    <h4 style="color: rgba(255, 255, 255, 0.8) !important;">No se encontraron productos</h4>
                    <p style="color: rgba(255, 255, 255, 0.6) !important;">No hay productos que coincidan con los filtros seleccionados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th>Código de Barras</th>
                                <?php if ($hasCategoria): ?><th>Categoría</th><?php endif; ?>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <?php if ($hasFechaCreacion): ?><th>Fecha</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td><?= $producto['id'] ?></td>
                                    <td>
                                        <strong style="color: white !important;"><?= htmlspecialchars($producto['nombre']) ?></strong>
                                        <?php if ($hasDescripcion && !empty($producto['descripcion'])): ?>
                                            <br><small style="color: rgba(255, 255, 255, 0.7) !important;"><?= htmlspecialchars(substr($producto['descripcion'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['sku'])): ?>
                                            <code style="background: rgba(255, 255, 255, 0.1) !important; color: #93c5fd !important; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($producto['sku']) ?></code>
                                        <?php else: ?>
                                            <span style="color: rgba(255, 255, 255, 0.5) !important;">Sin SKU</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['codigo_barras'])): ?>
                                            <code style="background: rgba(255, 255, 255, 0.1) !important; color: #93c5fd !important; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($producto['codigo_barras']) ?></code>
                                        <?php else: ?>
                                            <span style="color: rgba(255, 255, 255, 0.5) !important;">Sin código</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($hasCategoria): ?>
                                    <td>
                                        <?php if (!empty($producto['categoria'])): ?>
                                            <span class="badge" style="background: rgba(75, 85, 99, 0.8) !important; color: white !important; border: 1px solid rgba(156, 163, 175, 0.3);"><?= htmlspecialchars($producto['categoria']) ?></span>
                                        <?php else: ?>
                                            <span style="color: rgba(255, 255, 255, 0.5) !important;">Sin categoría</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td class="text-end">
                                        <strong style="color: #86efac !important;">$<?= number_format((int)$producto['precio']) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $producto['stock'] <= 10 ? 'bg-warning text-dark' : ($producto['stock'] <= 5 ? 'bg-danger' : 'bg-success') ?>">
                                            <?= number_format($producto['stock']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($producto['stock'] <= 0): ?>
                                            <span class="badge bg-danger">Agotado</span>
                                        <?php elseif ($producto['stock'] <= 5): ?>
                                            <span class="badge bg-danger">Crítico</span>
                                        <?php elseif ($producto['stock'] <= 10): ?>
                                            <span class="badge bg-warning text-dark">Bajo</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($hasFechaCreacion): ?>
                                    <td>
                                        <?php if (!empty($producto['fecha_creacion'])): ?>
                                            <small style="color: rgba(255, 255, 255, 0.7) !important;">
                                                <?= date('d/m/Y', strtotime($producto['fecha_creacion'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <span style="color: rgba(255, 255, 255, 0.5) !important;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Exportación -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Exportar Inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Seleccione el formato de exportación:</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success" onclick="exportarCSV()">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Exportar como CSV
                    </button>
                    <button class="btn btn-outline-primary" onclick="exportarJSON()">
                        <i class="bi bi-file-earmark-code"></i> Exportar como JSON
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Función para exportar CSV
function exportarCSV() {
    const productos = <?= json_encode($productos) ?>;
    let csv = 'ID,Nombre,SKU,Codigo de Barras,Categoria,Precio,Stock,Descripcion,Fecha\n';
    
    productos.forEach(producto => {
        csv += [
            producto.id,
            '"' + (producto.nombre || '') + '"',
            '"' + (producto.sku || '') + '"',
            '"' + (producto.codigo_barras || '') + '"',
            '"' + (producto.categoria || '') + '"',
            producto.precio,
            producto.stock,
            '"' + (producto.descripcion || '') + '"',
            '"' + (producto.fecha_creacion || '') + '"'
        ].join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'inventario_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    // Cerrar modal
    bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
}

// Función para exportar JSON
function exportarJSON() {
    const productos = <?= json_encode($productos) ?>;
    const dataStr = JSON.stringify(productos, null, 2);
    const blob = new Blob([dataStr], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'inventario_' + new Date().toISOString().split('T')[0] + '.json';
    a.click();
    window.URL.revokeObjectURL(url);
    
    // Cerrar modal
    bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
}

// Auto-actualizar cada 5 minutos
setInterval(function() {
    if (confirm('¿Desea actualizar el inventario? (datos actualizados cada 5 minutos)')) {
        location.reload();
    }
}, 300000); // 5 minutos

// Crear estrellas para el inventario
function createInventoryStars() {
    const container = document.getElementById('inventoryStars');
    const starCount = 20;
    
    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.className = 'inventory-star';
        star.style.left = Math.random() * 100 + '%';
        star.style.top = Math.random() * 100 + '%';
        star.style.width = (Math.random() * 3 + 1) + 'px';
        star.style.height = (Math.random() * 3 + 1) + 'px';
        star.style.animationDelay = Math.random() * 6 + 's';
        star.style.animationDuration = (Math.random() * 4 + 4) + 's';
        
        // Algunas estrellas más difuminadas
        if (Math.random() > 0.7) {
            star.style.filter = 'blur(1px)';
            star.style.opacity = '0.4';
        }
        
        container.appendChild(star);
    }
}

// Animaciones de entrada para cards
function animateInventoryCards() {
    const cards = document.querySelectorAll('.card');
    const rows = document.querySelectorAll('tr');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + (index * 100));
    });
    
    // Animar filas de la tabla
    rows.forEach((row, index) => {
        if (index > 0) { // Skip header
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(() => {
                row.style.transition = 'all 0.4s ease-out';
                row.style.opacity = '1';
                row.style.transform = 'translateX(0)';
            }, 300 + (index * 50));
        }
    });
}

// Inicializar animaciones
document.addEventListener('DOMContentLoaded', function() {
    createInventoryStars();
    animateInventoryCards();
    
    // Agregar efectos hover mejorados a las tarjetas de estadísticas
    const statCards = document.querySelectorAll('.border-primary, .border-success, .border-warning, .border-info');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>

<style media="print">
    .btn, .modal, .card-header .d-flex, nav, .inventory-stars, .sidebar-toggle {
        display: none !important;
    }
    .main-content {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        background: white !important;
        color: black !important;
    }
    .content-wrapper {
        padding: 0 !important;
    }
    .table {
        font-size: 12px;
        color: black !important;
    }
    .sidebar {
        display: none !important;
    }
    .card {
        background: white !important;
        color: black !important;
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .card-header {
        background: #f8f9fa !important;
        color: black !important;
    }
    h2, h4, h5 {
        color: black !important;
        text-shadow: none !important;
    }
    .badge {
        color: black !important;
        background: #f8f9fa !important;
        border: 1px solid #ddd !important;
    }
    .text-primary, .text-success, .text-warning, .text-info {
        color: black !important;
    }
    .container-fluid {
        color: black !important;
    }
</style>

<?php include '../../includes/footer_vendedor.php'; ?>
