<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/funciones.php'; // CSRF helpers
require_once '../../includes/db.php';

// Manejar acciones POST
// CSRF validation para formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
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
                if ($stmt->execute([$nombre, $precio, $stock, $sku, $codigo_barras])) {
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
                if ($stmt->execute([$nombre, $precio, $stock, $sku, $codigo_barras, $id])) {
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
                if ($stmt->execute([$id])) {
                    $success = "Producto eliminado exitosamente";
                } else {
                    $error = "Error al eliminar producto: " . $conn->error;
                }
            }
            break;
            
        case 'bulk_upload':
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $csvFile = $_FILES['csv_file']['tmp_name'];
                $updateExisting = isset($_POST['update_existing']);
                
                if (($handle = fopen($csvFile, 'r')) !== FALSE) {
                    $rowCount = 0;
                    $successCount = 0;
                    $errorCount = 0;
                    $errors = [];
                    
                    // Skip header row if exists
                    $firstRow = fgetcsv($handle);
                    if ($firstRow && strtolower($firstRow[0]) === 'nombre') {
                        // Skip header
                    } else {
                        // Reset file pointer to beginning
                        rewind($handle);
                    }
                    
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $rowCount++;
                        
                        if (count($data) < 3) {
                            $errors[] = "Fila $rowCount: Datos insuficientes";
                            $errorCount++;
                            continue;
                        }
                        
                        $nombre = trim($data[0] ?? '');
                        $precio = (int)($data[1] ?? 0);
                        $stock = (int)($data[2] ?? 0);
                        $sku = trim($data[3] ?? '');
                        $codigo_barras = trim($data[4] ?? '');
                        
                        if (empty($nombre) || $precio < 0 || $stock < 0) {
                            $errors[] = "Fila $rowCount: Datos inválidos (nombre: '$nombre', precio: $precio, stock: $stock)";
                            $errorCount++;
                            continue;
                        }
                        
                        try {
                            if ($updateExisting && !empty($sku)) {
                                // Check if product exists by SKU
                                $checkStmt = $conn->prepare("SELECT id FROM productos WHERE sku = ?");
                                $checkStmt->execute([$sku]);
                                
                                if ($checkStmt->fetch()) {
                                    // Update existing product
                                    $updateStmt = $conn->prepare("UPDATE productos SET nombre = ?, precio = ?, stock = ?, codigo_barras = ? WHERE sku = ?");
                                    $updateStmt->execute([$nombre, $precio, $stock, $codigo_barras, $sku]);
                                } else {
                                    // Insert new product
                                    $insertStmt = $conn->prepare("INSERT INTO productos (nombre, precio, stock, sku, codigo_barras) VALUES (?, ?, ?, ?, ?)");
                                    $insertStmt->execute([$nombre, $precio, $stock, $sku, $codigo_barras]);
                                }
                            } else {
                                // Always insert new product
                                $insertStmt = $conn->prepare("INSERT INTO productos (nombre, precio, stock, sku, codigo_barras) VALUES (?, ?, ?, ?, ?)");
                                $insertStmt->execute([$nombre, $precio, $stock, $sku, $codigo_barras]);
                            }
                            
                            $successCount++;
                        } catch (Exception $e) {
                            $errors[] = "Fila $rowCount: Error al procesar - " . $e->getMessage();
                            $errorCount++;
                        }
                    }
                    
                    fclose($handle);
                    
                    if ($successCount > 0) {
                        $success = "Procesamiento completado: $successCount productos procesados exitosamente";
                        if ($errorCount > 0) {
                            $success .= ", $errorCount errores encontrados";
                        }
                    } else {
                        $error = "No se pudo procesar ningún producto. Errores: " . implode('; ', array_slice($errors, 0, 3));
                    }
                } else {
                    $error = "Error al leer el archivo CSV";
                }
            } else {
                $error = "Error al subir el archivo";
            }
            break;
    }
}

// Obtener productos con paginación y ordenamiento
$page = (int)($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'nombre';
$sort_order = $_GET['sort_order'] ?? 'ASC';
$stock_filter = $_GET['stock_filter'] ?? '';

// Validar columnas de ordenamiento
$valid_sorts = ['id', 'nombre', 'precio', 'stock', 'sku', 'codigo_barras'];
if (!in_array($sort_by, $valid_sorts)) {
    $sort_by = 'nombre';
}
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(nombre LIKE ? OR sku LIKE ? OR codigo_barras LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Filtro de stock
if (!empty($stock_filter)) {
    switch ($stock_filter) {
        case 'bajo':
            $where_conditions[] = "stock <= 10";
            break;
        case 'medio':
            $where_conditions[] = "stock > 10 AND stock <= 50";
            break;
        case 'alto':
            $where_conditions[] = "stock > 50";
            break;
        case 'agotado':
            $where_conditions[] = "stock = 0";
            break;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total de productos
$countQuery = "SELECT COUNT(*) as total FROM productos $where_clause";
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalProducts / $limit);

// Obtener productos con ordenamiento
$query = "SELECT * FROM productos $where_clause ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
foreach ($params as $index => $value) {
    $stmt->bindValue($index + 1, $value); // +1 porque los índices de PDO comienzan en 1
}

$stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas de stock
$statsQuery = "SELECT 
    COUNT(*) as total_productos,
    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as productos_agotados,
    SUM(CASE WHEN stock <= 10 AND stock > 0 THEN 1 ELSE 0 END) as stock_bajo,
    SUM(CASE WHEN stock > 10 AND stock <= 50 THEN 1 ELSE 0 END) as stock_medio,
    SUM(CASE WHEN stock > 50 THEN 1 ELSE 0 END) as stock_alto,
    AVG(precio) as precio_promedio,
    SUM(stock * precio) as valor_inventario
FROM productos";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Página de productos - Compatible con tema claro/oscuro */
.productos-container {
    padding: 0;
}

.productos-header {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: var(--shadow-sm);
}

.productos-title {
    color: var(--text-primary);
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0;
    background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
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

.search-input, .filter-select {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    color: var(--text-primary);
    font-size: 0.95rem;
    transition: var(--transition);
}

.search-input {
    min-width: 250px;
}

.filter-select {
    min-width: 150px;
}

.search-input::placeholder {
    color: var(--text-muted);
}

.search-input:focus, .filter-select:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-select option {
    background: var(--bg-secondary);
    color: var(--text-primary);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--border-light);
}

.stat-card:hover::before {
    opacity: 1;
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
    color: white;
}

.stat-icon.total { 
    background: var(--accent-primary);
}
.stat-icon.total + .stat-card::before { 
    background: var(--accent-primary);
}

.stat-icon.agotado { 
    background: var(--accent-danger);
}
.stat-icon.agotado + .stat-card::before { 
    background: var(--accent-danger);
}

.stat-icon.bajo { 
    background: var(--accent-warning);
}
.stat-icon.bajo + .stat-card::before { 
    background: var(--accent-warning);
}

.stat-icon.medio { 
    background: var(--accent-info);
}
.stat-icon.medio + .stat-card::before { 
    background: var(--accent-info);
}

.stat-icon.alto { 
    background: var(--accent-success);
}
.stat-icon.alto + .stat-card::before { 
    background: var(--accent-success);
}

.stat-icon.valor { 
    background: var(--accent-secondary);
}
.stat-icon.valor + .stat-card::before { 
    background: var(--accent-secondary);
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
}

.btn-primary {
    background: var(--accent-primary);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    cursor: pointer;
}

.btn-primary:hover {
    background: var(--accent-primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    color: white;
    text-decoration: none;
}

.btn-bulk {
    background: var(--accent-secondary);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    cursor: pointer;
}

.btn-bulk:hover {
    background: var(--accent-secondary-hover);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
    color: white;
    text-decoration: none;
}

.table-container {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    overflow-x: auto;
    box-shadow: var(--shadow-sm);
}

.productos-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.productos-table th, .productos-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.productos-table th {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    font-weight: 600;
    position: sticky;
    top: 0;
    cursor: pointer;
    transition: var(--transition);
    user-select: none;
}

.productos-table th:hover {
    background: var(--bg-secondary);
}

.productos-table td {
    color: var(--text-primary);
}

.productos-table tr:hover {
    background: var(--bg-tertiary);
}

.sort-icon {
    margin-left: 0.5rem;
    font-size: 0.8rem;
}

.stock-alert {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.stock-agotado {
    background: rgba(239, 68, 68, 0.1);
    color: var(--accent-danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.stock-bajo {
    background: rgba(245, 158, 11, 0.1);
    color: var(--accent-warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.stock-medio {
    background: rgba(59, 130, 246, 0.1);
    color: var(--accent-primary);
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.stock-alto {
    background: rgba(16, 185, 129, 0.1);
    color: var(--accent-success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.product-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 1.5rem;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.product-card:hover {
    transform: translateY(-4px);
    border-color: var(--border-light);
    box-shadow: var(--shadow-md);
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.product-name {
    color: var(--text-primary);
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
    transition: var(--transition);
    color: white;
    font-weight: 500;
}

.btn-edit {
    background: var(--accent-warning);
}

.btn-delete {
    background: var(--accent-danger);
}

.btn-sm:hover {
    transform: scale(1.05);
    opacity: 0.9;
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
    color: var(--text-muted);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.info-value {
    color: var(--text-primary);
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
    background: rgba(16, 185, 129, 0.1);
    color: var(--accent-success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.stock-medium {
    background: rgba(245, 158, 11, 0.1);
    color: var(--accent-warning);
    border: 1px solid rgba(245, 158, 11, 0.2);
}

.stock-low {
    background: rgba(239, 68, 68, 0.1);
    color: var(--accent-danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-btn {
    padding: 0.5rem 1rem;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    text-decoration: none;
    transition: var(--transition);
    font-weight: 500;
}

.page-btn:hover, .page-btn.active {
    background: var(--accent-primary);
    color: white;
    text-decoration: none;
    border-color: var(--accent-primary);
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
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
}

.modal-content {
    background: var(--bg-card);
    margin: 5% auto;
    padding: 2rem;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    position: relative;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-title {
    color: var(--text-primary);
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
    transition: var(--transition);
    padding: 0.5rem;
    border-radius: 8px;
}

.close:hover {
    color: var(--text-primary);
    background: var(--bg-tertiary);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-tertiary);
    color: var(--text-primary);
    font-size: 0.95rem;
    transition: var(--transition);
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control::placeholder {
    color: var(--text-muted);
}

.btn-secondary {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    color: var(--text-primary);
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    cursor: pointer;
}

.btn-secondary:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    text-decoration: none;
}

.btn-delete {
    background: var(--accent-danger);
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition);
    cursor: pointer;
}

.btn-delete:hover {
    background: var(--accent-danger-hover);
    color: white;
    text-decoration: none;
    transform: translateY(-1px);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.form-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
}

.form-check-input {
    width: 16px;
    height: 16px;
}

.form-check-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-weight: 500;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--accent-success);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: var(--accent-danger);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .productos-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1.5rem;
    }
    
    .search-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input, .filter-select {
        min-width: auto;
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .table-container {
        padding: 1rem;
    }
    
    .modal-content {
        margin: 10% auto;
        width: 95%;
        padding: 1.5rem;
    }
    
    .product-info {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .productos-table th,
    .productos-table td {
        padding: 0.5rem;
        font-size: 0.85rem;
    }
}

/* Animaciones y efectos de carga */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-loading {
    pointer-events: none;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

/* Mejoras en la navegación de páginas */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.page-btn {
    padding: 0.75rem 1rem;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    text-decoration: none;
    transition: var(--transition);
    font-weight: 500;
    min-width: 44px;
    text-align: center;
    cursor: pointer;
    user-select: none;
}

.page-btn:hover {
    background: var(--accent-primary);
    color: white;
    text-decoration: none;
    border-color: var(--accent-primary);
    transform: translateY(-1px);
}

.page-btn.active {
    background: var(--accent-primary);
    color: white;
    border-color: var(--accent-primary);
    font-weight: 600;
}

.page-btn:active {
    transform: translateY(0);
}

/* Efectos de carga para la tabla */
.table-loading {
    position: relative;
}

.table-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(var(--bg-primary-rgb), 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

/* Scroll suave para navegación */
html {
    scroll-behavior: smooth;
}

/* Indicador de posición en la tabla */
.table-container {
    position: relative;
    scroll-margin-top: 2rem;
}

.table-position-indicator {
    position: absolute;
    top: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 2px 2px 0 0;
}

.table-container.highlight .table-position-indicator {
    opacity: 1;
}

/* Mejoras en las columnas ordenables */
.productos-table th {
    position: relative;
}

.productos-table th:hover {
    background: var(--bg-secondary);
}

.productos-table th.sorting {
    background: var(--bg-secondary);
}

.sort-icon {
    margin-left: 0.5rem;
    font-size: 0.8rem;
    opacity: 0.6;
    transition: var(--transition);
}

.productos-table th:hover .sort-icon {
    opacity: 1;
}
</style>

<div class="productos-container">
    <!-- Header -->
    <div class="productos-header">
        <h1 class="productos-title">Gestión de Productos</h1>
        <div class="search-controls">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <input type="text" name="search" class="search-input" placeholder="Buscar por nombre, SKU o código..." value="<?= htmlspecialchars($search) ?>">
                
                <select name="stock_filter" class="filter-select">
                    <option value="">Todos los stocks</option>
                    <option value="agotado" <?= $stock_filter === 'agotado' ? 'selected' : '' ?>>Agotado</option>
                    <option value="bajo" <?= $stock_filter === 'bajo' ? 'selected' : '' ?>>Stock Bajo (≤10)</option>
                    <option value="medio" <?= $stock_filter === 'medio' ? 'selected' : '' ?>>Stock Medio (11-50)</option>
                    <option value="alto" <?= $stock_filter === 'alto' ? 'selected' : '' ?>>Stock Alto (>50)</option>
                </select>
                
                <!-- Hidden sort parameters -->
                <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
                <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sort_order) ?>">
                
                <button type="submit" class="btn-primary">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </form>
            
            <button class="btn-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-circle"></i> Nuevo Producto
            </button>
            
            <button class="btn-bulk" onclick="openBulkModal()">
                <i class="bi bi-upload"></i> Ingreso Masivo
            </button>
        </div>
    </div>

    <!-- Estadísticas de Inventario -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">📦</div>
            <div class="stat-value"><?= number_format($stats['total_productos']) ?></div>
            <div class="stat-label">Total Productos</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon agotado">⛔</div>
            <div class="stat-value"><?= number_format($stats['productos_agotados']) ?></div>
            <div class="stat-label">Productos Agotados</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bajo">⚠️</div>
            <div class="stat-value"><?= number_format($stats['stock_bajo']) ?></div>
            <div class="stat-label">Stock Bajo</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon medio">📊</div>
            <div class="stat-value"><?= number_format($stats['stock_medio']) ?></div>
            <div class="stat-label">Stock Medio</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon alto">✅</div>
            <div class="stat-value"><?= number_format($stats['stock_alto']) ?></div>
            <div class="stat-label">Stock Alto</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon valor">💎</div>
            <div class="stat-value">$<?= number_format($stats['valor_inventario'], 0, ',', '.') ?></div>
            <div class="stat-label">Valor Inventario</div>
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

    <!-- Tabla de productos con ordenamiento -->
    <div class="table-container">
        <h3 style="color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-table"></i> 
            Productos (<?= number_format($totalProducts) ?> encontrados)
        </h3>
        
        <table class="productos-table">
            <thead>
                <tr>
                    <th onclick="sortTable('id')">
                        ID 
                        <span class="sort-icon">
                            <?= $sort_by === 'id' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('nombre')">
                        Nombre
                        <span class="sort-icon">
                            <?= $sort_by === 'nombre' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('precio')">
                        Precio
                        <span class="sort-icon">
                            <?= $sort_by === 'precio' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('stock')">
                        Stock
                        <span class="sort-icon">
                            <?= $sort_by === 'stock' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('sku')">
                        SKU
                        <span class="sort-icon">
                            <?= $sort_by === 'sku' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th onclick="sortTable('codigo_barras')">
                        Código de Barras
                        <span class="sort-icon">
                            <?= $sort_by === 'codigo_barras' ? ($sort_order === 'ASC' ? '↑' : '↓') : '↕' ?>
                        </span>
                    </th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($productos)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem;"></i>
                        No se encontraron productos con los filtros aplicados
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($productos as $producto): ?>
                    <?php
                    $stockClass = '';
                    $stockIcon = '';
                    if ($producto['stock'] == 0) {
                        $stockClass = 'stock-agotado';
                        $stockIcon = '⛔';
                    } elseif ($producto['stock'] <= 10) {
                        $stockClass = 'stock-bajo';
                        $stockIcon = '⚠️';
                    } elseif ($producto['stock'] <= 50) {
                        $stockClass = 'stock-medio';
                        $stockIcon = '📊';
                    } else {
                        $stockClass = 'stock-alto';
                        $stockIcon = '✅';
                    }
                    ?>
                    <tr>
                        <td><strong>#<?= htmlspecialchars($producto['id']) ?></strong></td>
                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                        <td><strong>$<?= number_format($producto['precio'], 0, ',', '.') ?></strong></td>
                        <td>
                            <span class="stock-alert <?= $stockClass ?>">
                                <?= $stockIcon ?> <?= htmlspecialchars($producto['stock']) ?>
                            </span>
                        </td>
                        <td><code><?= htmlspecialchars($producto['sku'] ?? '') ?></code></td>
                        <td><code><?= htmlspecialchars($producto['codigo_barras'] ?? '') ?></code></td>
                        <td>
                            <button class="btn-sm btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($producto)) ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-sm btn-delete" onclick="confirmDelete(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="page-btn <?= $i == $page ? 'active' : '' ?>" 
                   href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para crear/editar producto -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Nuevo Producto</h2>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="productForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
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
        
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            ¿Estás seguro de que deseas eliminar el producto "<span id="deleteProductName"></span>"?
            Esta acción no se puede deshacer.
        </p>
        
        <form id="deleteForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteProductId">
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button type="submit" class="btn-delete">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para ingreso masivo -->
<div id="bulkModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2 class="modal-title">Ingreso Masivo de Productos</h2>
            <button class="close" onclick="closeBulkModal()">&times;</button>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h4 style="color: var(--text-primary); margin-bottom: 1rem;">Formato CSV</h4>
            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                El archivo CSV debe tener las siguientes columnas en orden:
            </p>
            <div style="background: var(--bg-tertiary); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                <code style="color: var(--accent-primary);">nombre,precio,stock,sku,codigo_barras</code>
            </div>
            
            <h5 style="color: var(--text-primary); margin-bottom: 0.5rem;">Ejemplo:</h5>
            <div style="background: var(--bg-tertiary); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border-color);">
                <code style="color: var(--accent-success);">
                    Coca Cola 350ml,1500,100,COC350,7801234567890<br>
                    Pan Integral,800,50,PAN001,7801234567891<br>
                    Leche Entera 1L,1200,75,LEC1L,7801234567892
                </code>
            </div>
        </div>
        
        <form id="bulkForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="bulk_upload">
            
            <div class="form-group">
                <label class="form-label" for="csv_file">Archivo CSV *</label>
                <input type="file" name="csv_file" id="csv_file" class="form-control" 
                       accept=".csv" required>
                <small style="color: var(--text-muted); font-size: 0.8rem;">
                    Solo archivos CSV. Máximo 5MB.
                </small>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary);">
                    <input type="checkbox" name="update_existing" value="1" 
                           style="margin: 0; transform: scale(1.2);">
                    Actualizar productos existentes (basado en SKU)
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeBulkModal()">Cancelar</button>
                <button type="submit" class="btn-bulk">
                    <i class="bi bi-upload"></i> Procesar Archivo
                </button>
            </div>
        </form>
        
        <div id="bulkProgress" style="display: none; margin-top: 1rem;">
            <div style="background: var(--bg-tertiary); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <p style="color: var(--text-primary); margin: 0;">Procesando archivo...</p>
                <div style="background: var(--border-color); height: 8px; border-radius: 4px; margin-top: 0.5rem; overflow: hidden;">
                    <div id="progressBar" style="background: linear-gradient(90deg, var(--accent-primary), var(--accent-success)); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones de modal
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

function openBulkModal() {
    document.getElementById('bulkModal').style.display = 'block';
}

function closeBulkModal() {
    document.getElementById('bulkModal').style.display = 'none';
    document.getElementById('bulkForm').reset();
    document.getElementById('bulkProgress').style.display = 'none';
}

function confirmDelete(id, nombre) {
    document.getElementById('deleteProductId').value = id;
    document.getElementById('deleteProductName').textContent = nombre;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function sortTable(column) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort_by');
    const currentOrder = urlParams.get('sort_order');
    
    let newOrder = 'ASC';
    if (currentSort === column && currentOrder === 'ASC') {
        newOrder = 'DESC';
    }
    
    urlParams.set('sort_by', column);
    urlParams.set('sort_order', newOrder);
    urlParams.delete('page'); // Reset to first page when sorting
    
    window.location.search = urlParams.toString();
}

// Función mejorada para navegación de páginas
function goToPage(page) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    
    window.location.search = urlParams.toString();
}

// Handle bulk upload form
document.getElementById('bulkForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('csv_file');
    if (!fileInput.files.length) {
        alert('Por favor selecciona un archivo CSV');
        return;
    }
    
    const file = fileInput.files[0];
    if (file.size > 5 * 1024 * 1024) { // 5MB
        alert('El archivo es demasiado grande. Máximo 5MB.');
        return;
    }
    
    if (!file.name.toLowerCase().endsWith('.csv')) {
        alert('Por favor selecciona un archivo CSV válido');
        return;
    }
    
    // Show progress
    document.getElementById('bulkProgress').style.display = 'block';
    
    // Simulate progress (replace with actual upload logic)
    let progress = 0;
    const progressBar = document.getElementById('progressBar');
    const interval = setInterval(() => {
        progress += 10;
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            // Submit the form
            e.target.submit();
        }
    }, 100);
});

// Auto-submit search form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const stockFilter = document.querySelector('select[name="stock_filter"]');
    if (stockFilter) {
        stockFilter.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Agregar event listeners a todos los enlaces de paginación
    const pageLinks = document.querySelectorAll('.page-btn');
    pageLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Extraer el número de página del href
            const url = new URL(this.href);
            const page = url.searchParams.get('page') || 1;
            
            goToPage(page);
        });
    });
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const productModal = document.getElementById('productModal');
    const deleteModal = document.getElementById('deleteModal');
    const bulkModal = document.getElementById('bulkModal');
    
    if (event.target == productModal) {
        productModal.style.display = 'none';
    }
    if (event.target == deleteModal) {
        deleteModal.style.display = 'none';
    }
    if (event.target == bulkModal) {
        bulkModal.style.display = 'none';
    }
}
</script>
