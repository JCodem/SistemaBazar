<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nombre = trim($_POST['nombre']);
            $precio = (int)$_POST['precio'];
            $stock = (int)$_POST['stock'];
            $sku = trim($_POST['sku'] ?? '');
            $codigo_barras = trim($_POST['codigo_barras'] ?? '');
            
            if (!empty($nombre) && $precio >= 0 && $stock >= 0) {
                $stmt = $conn->prepare("INSERT INTO productos (nombre, precio, stock, sku, codigo_barras) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("siiss", $nombre, $precio, $stock, $sku, $codigo_barras);
                
                if ($stmt->execute()) {
                    $success = "Producto creado exitosamente";
                } else {
                    $error = "Error al crear producto: " . $conn->error;
                }
            } else {
                $error = "Todos los campos obligatorios deben ser completados";
            }
            break;
            
        case 'update':
            $id = (int)$_POST['id'];
            $nombre = trim($_POST['nombre']);
            $precio = (int)$_POST['precio'];
            $stock = (int)$_POST['stock'];
            $sku = trim($_POST['sku'] ?? '');
            $codigo_barras = trim($_POST['codigo_barras'] ?? '');
            
            if ($id > 0 && !empty($nombre) && $precio >= 0 && $stock >= 0) {
                $stmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, stock = ?, sku = ?, codigo_barras = ? WHERE id = ?");
                $stmt->bind_param("siissi", $nombre, $precio, $stock, $sku, $codigo_barras, $id);
                
                if ($stmt->execute()) {
                    $success = "Producto actualizado exitosamente";
                } else {
                    $error = "Error al actualizar producto: " . $conn->error;
                }
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success = "Producto eliminado exitosamente";
                } else {
                    $error = "Error al eliminar producto: " . $conn->error;
                }
            }
            break;
    }
}

// Obtener productos con paginación
$page = (int)($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

$where = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where = "WHERE nombre LIKE ? OR sku LIKE ? OR codigo_barras LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = 'sss';
}

// Contar total de productos
$countQuery = "SELECT COUNT(*) as total FROM productos $where";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalProducts = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalProducts / $limit);

// Obtener productos
$query = "SELECT * FROM productos $where ORDER BY nombre ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<style>
.productos-container {
    padding: 0;
}

.productos-header {
    background: linear-gradient(135deg, rgba(45, 27, 105, 0.1), rgba(17, 153, 142, 0.1));
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.productos-title {
    color: #ffffff;
    font-size: 2.2rem;
    font-weight: 300;
    margin: 0;
    background: linear-gradient(135deg, #ff6b6b, #feca57, #48dbfb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.search-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-input {
    background: rgba(30, 30, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    color: white;
    font-size: 0.95rem;
    min-width: 250px;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.btn-primary {
    background: linear-gradient(135deg, #48dbfb, #0abde3);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(72, 219, 251, 0.3);
    color: white;
    text-decoration: none;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.product-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.product-card:hover {
    transform: translateY(-8px);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.product-name {
    color: #ffffff;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0;
    line-height: 1.3;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    border: none;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit {
    background: linear-gradient(135deg, #feca57, #ff9f43);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    color: white;
}

.btn-sm:hover {
    transform: scale(1.05);
}

.product-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: #ffffff;
    font-weight: 600;
    font-size: 1rem;
}

.stock-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: center;
}

.stock-high {
    background: rgba(0, 255, 127, 0.2);
    color: #00ff7f;
    border: 1px solid rgba(0, 255, 127, 0.3);
}

.stock-medium {
    background: rgba(254, 202, 87, 0.2);
    color: #feca57;
    border: 1px solid rgba(254, 202, 87, 0.3);
}

.stock-low {
    background: rgba(255, 107, 107, 0.2);
    color: #ff6b6b;
    border: 1px solid rgba(255, 107, 107, 0.3);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-btn {
    padding: 0.5rem 1rem;
    background: rgba(30, 30, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.page-btn:hover, .page-btn.active {
    background: linear-gradient(135deg, #48dbfb, #0abde3);
    color: white;
    text-decoration: none;
}

/* Modal Styles */
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
    position: relative;
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

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
}

.btn-secondary {
    background: rgba(100, 100, 100, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: rgba(100, 100, 100, 0.5);
}

/* Responsive */
@media (max-width: 768px) {
    .productos-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-controls {
        justify-content: center;
    }
    
    .search-input {
        min-width: auto;
        width: 100%;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .product-info {
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

<div class="productos-container">
    <!-- Header -->
    <div class="productos-header">
        <h1 class="productos-title">Gestión de Productos</h1>
        <div class="search-controls">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                <input type="text" name="search" class="search-input" placeholder="Buscar por nombre, SKU o código de barras..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-primary">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </form>
            <button class="btn-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-circle"></i> Nuevo Producto
            </button>
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

    <!-- Grid de productos -->
    <div class="products-grid">
        <?php foreach ($productos as $producto): ?>
            <div class="product-card">
                <div class="product-header">
                    <h3 class="product-name"><?= htmlspecialchars($producto['nombre']) ?></h3>
                    <div class="product-actions">
                        <button class="btn-sm btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($producto)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-sm btn-delete" onclick="confirmDelete(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="product-info">
                    <div class="info-item">
                        <span class="info-label">Precio</span>
                        <span class="info-value">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Stock</span>
                        <span class="stock-badge <?= $producto['stock'] <= 5 ? 'stock-low' : ($producto['stock'] <= 20 ? 'stock-medium' : 'stock-high') ?>">
                            <?= $producto['stock'] ?> unidades
                        </span>
                    </div>
                    <?php if (!empty($producto['sku'])): ?>
                    <div class="info-item">
                        <span class="info-label">SKU</span>
                        <span class="info-value"><?= htmlspecialchars($producto['sku']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($producto['codigo_barras'])): ?>
                    <div class="info-item">
                        <span class="info-label">Código de Barras</span>
                        <span class="info-value"><?= htmlspecialchars($producto['codigo_barras']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                   class="page-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para crear/editar producto -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Nuevo Producto</h2>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="productForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="productId">
            
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre del Producto *</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="precio">Precio *</label>
                <input type="number" name="precio" id="precio" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="stock">Stock *</label>
                <input type="number" name="stock" id="stock" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="sku">SKU</label>
                <input type="text" name="sku" id="sku" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="codigo_barras">Código de Barras</label>
                <input type="text" name="codigo_barras" id="codigo_barras" class="form-control">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Confirmar Eliminación</h2>
            <button class="close" onclick="closeDeleteModal()">&times;</button>
        </div>
        
        <p style="color: rgba(255, 255, 255, 0.8); margin-bottom: 2rem;">
            ¿Estás seguro de que deseas eliminar el producto "<span id="deleteProductName"></span>"?
            Esta acción no se puede deshacer.
        </p>
        
        <form id="deleteForm" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteProductId">
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button type="submit" class="btn-delete">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nuevo Producto';
    document.getElementById('formAction').value = 'create';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('productModal').style.display = 'block';
}

function openEditModal(producto) {
    document.getElementById('modalTitle').textContent = 'Editar Producto';
    document.getElementById('formAction').value = 'update';
    document.getElementById('productId').value = producto.id;
    document.getElementById('nombre').value = producto.nombre;
    document.getElementById('precio').value = producto.precio;
    document.getElementById('stock').value = producto.stock;
    document.getElementById('sku').value = producto.sku || '';
    document.getElementById('codigo_barras').value = producto.codigo_barras || '';
    document.getElementById('productModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('productModal').style.display = 'none';
}

function confirmDelete(id, nombre) {
    document.getElementById('deleteProductId').value = id;
    document.getElementById('deleteProductName').textContent = nombre;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const productModal = document.getElementById('productModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target == productModal) {
        productModal.style.display = 'none';
    }
    if (event.target == deleteModal) {
        deleteModal.style.display = 'none';
    }
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
