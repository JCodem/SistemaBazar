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

<div class="container-fluid">
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
        <div class="card-header bg-light">
            <h5 class="mb-0">Lista de Productos (<?= $totalProductos ?> productos)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($productos)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h4 class="text-muted">No se encontraron productos</h4>
                    <p class="text-muted">No hay productos que coincidan con los filtros seleccionados.</p>
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
                                        <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                        <?php if ($hasDescripcion && !empty($producto['descripcion'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($producto['descripcion'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['sku'])): ?>
                                            <code><?= htmlspecialchars($producto['sku']) ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">Sin SKU</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($producto['codigo_barras'])): ?>
                                            <code><?= htmlspecialchars($producto['codigo_barras']) ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">Sin código</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($hasCategoria): ?>
                                    <td>
                                        <?php if (!empty($producto['categoria'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($producto['categoria']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin categoría</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td class="text-end">
                                        <strong>$<?= number_format((int)$producto['precio']) ?></strong>
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
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($producto['fecha_creacion'])) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
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

<!-- Enlaces a Bootstrap y Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
</script>

<style media="print">
    .btn, .modal, .card-header .d-flex, nav, .sidebar {
        display: none !important;
    }
    .main-content {
        padding: 0 !important;
        margin: 0 !important;
    }
    .table {
        font-size: 12px;
    }
</style>

</div> <!-- Cierre del main-content del layout -->
</body>
</html>
