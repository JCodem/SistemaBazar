<?php 
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $rut = trim($_POST['rut']);
            $rol = $_POST['rol'];
            $contrasena = $_POST['contrasena'];
            
            if (!empty($nombre) && !empty($correo) && !empty($rut) && !empty($contrasena)) {
                // Verificar si el correo o RUT ya existen
                $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = ? OR rut = ?");
                $checkStmt->execute([$correo, $rut]);

                if ($checkStmt->rowCount() > 0) {
                    $error = "El correo o RUT ya están registrados";
                } else {
                    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                    $created_at = date('Y-m-d H:i:s');

                    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, rut, rol, contrasena, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$nombre, $correo, $rut, $rol, $contrasena_hash, $created_at, $created_at])) {
                        $success = "Usuario creado exitosamente";
                    } else {
                        $error = "Error al crear usuario: " . $stmt->errorInfo()[2];
                    }
                }
            } else {
                $error = "Todos los campos son obligatorios";
            }
            break;
            
        case 'update':
            $id = (int)$_POST['id'];
            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $rut = trim($_POST['rut']);
            $rol = $_POST['rol'];
            $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
            
            if ($id > 0 && !empty($nombre) && !empty($correo) && !empty($rut)) {
                // Verificar si el correo o RUT ya existen en otros usuarios
                $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE (correo = ? OR rut = ?) AND id != ?");
                $checkStmt->execute([$correo, $rut, $id]);

                if ($checkStmt->rowCount() > 0) {
                    $error = "El correo o RUT ya están registrados por otro usuario";
                } else {
                    $updated_at = date('Y-m-d H:i:s');

                    if (!empty($nueva_contrasena)) {
                        // Actualizar con nueva contraseña
                        $contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare(
                            "UPDATE usuarios SET nombre = ?, correo = ?, rut = ?, rol = ?, contrasena = ?, updated_at = ? WHERE id = ?"
                        );
                        $executed = $stmt->execute([$nombre, $correo, $rut, $rol, $contrasena_hash, $updated_at, $id]);
                    } else {
                        // Actualizar sin cambiar contraseña
                        $stmt = $conn->prepare(
                            "UPDATE usuarios SET nombre = ?, correo = ?, rut = ?, rol = ?, updated_at = ? WHERE id = ?"
                        );
                        $executed = $stmt->execute([$nombre, $correo, $rut, $rol, $updated_at, $id]);
                    }

                    if ($executed) {
                        $success = "Usuario actualizado exitosamente";
                    } else {
                        $error = "Error al actualizar usuario: " . $stmt->errorInfo()[2];
                    }
                }
            }
            break;
            
        case 'delete':
            $id = (int)$_POST['id'];
            if ($id > 0) {
                // No permitir eliminar el usuario actual
                if ($id == $_SESSION['user_id']) {
                    $error = "No puedes eliminar tu propio usuario";
                } else {
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $success = "Usuario eliminado exitosamente";
                } else {
                    $error = "Error al eliminar usuario: " . $stmt->errorInfo()[2];
                }
                }
            }
            break;
            
        case 'toggle_status':
            $id = (int)$_POST['id'];
            $status = $_POST['status'];
            // Esta funcionalidad requeriría agregar un campo 'activo' a la tabla usuarios
            break;
    }
}

// Obtener usuarios con filtros
$search = $_GET['search'] ?? '';
$rol_filter = $_GET['rol'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 12;
$offset = ($page - 1) * $limit;

$where_conditions = ["rol != 'admin'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(nombre LIKE ? OR correo LIKE ? OR rut LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if (!empty($rol_filter)) {
    $where_conditions[] = "rol = ?";
    $params[] = $rol_filter;
    $types .= 's';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Contar usuarios con filtros
$countQuery = "SELECT COUNT(*) FROM usuarios $where_clause";
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalUsers / $limit);

// Obtener usuarios con paginación
$query = "SELECT id, nombre, correo, rut, rol, created_at, updated_at FROM usuarios $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

// Bind regular parameters first
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}

// Bind LIMIT and OFFSET as integers
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);

$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$statsQuery = "SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN rol = 'vendedor' THEN 1 ELSE 0 END) as vendedores,
    SUM(CASE WHEN rol = 'jefe' THEN 1 ELSE 0 END) as jefes,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as nuevos_hoy
FROM usuarios WHERE rol != 'admin'";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
.vendedores-container {
  padding: 2.5rem 2rem 2rem 2rem;
  margin-left: var(--sidebar-width, 260px);
  max-width: 1400px;
  width: calc(100% - var(--sidebar-width, 260px));
  box-sizing: border-box;
  min-height: 100vh;
  background: transparent;
  transition: margin-left 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), width 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

@media (max-width: 1200px) {
  .vendedores-container {
    max-width: 100%;
    padding: 2rem 1rem 1rem 1rem;
    width: 100%;
  }
}
@media (max-width: 992px) {
  .vendedores-container {
    margin-left: 0;
    width: 100%;
    padding: 1.5rem 0.5rem 1rem 0.5rem;
  }
}
@media (max-width: 768px) {
  .vendedores-container {
    margin-left: 0;
    padding: 1rem 0.5rem;
    width: 100%;
  }
}

.vendedores-header {
    background: linear-gradient(135deg, rgba(45, 27, 105, 0.1), rgba(17, 153, 142, 0.1));
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.vendedores-title {
    color: #ffffff;
    font-size: 2.2rem;
    font-weight: 300;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #ff6b6b, #feca57, #48dbfb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.overview-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.overview-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 255, 255, 0.2);
}

.overview-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.overview-icon.total { background: linear-gradient(135deg, #48dbfb, #74edf7); }
.overview-icon.vendedores { background: linear-gradient(135deg, #feca57, #ffd93d); }
.overview-icon.jefes { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); }
.overview-icon.nuevos { background: linear-gradient(135deg, #6c5ce7, #a29bfe); }

.overview-value {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.5rem;
}

.overview-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.controls-section {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(15px);
}

.controls-grid {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 1rem;
    align-items: end;
}

.search-filters {
    display: flex;
    gap: 1rem;
}

.filter-input, .filter-select {
    background: rgba(30, 30, 40, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    color: white;
    font-size: 0.95rem;
    min-width: 200px;
}

.filter-input::placeholder {
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
    white-space: nowrap;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(72, 219, 251, 0.3);
    color: white;
    text-decoration: none;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.user-card {
    background: linear-gradient(135deg, rgba(30, 30, 40, 0.9), rgba(45, 27, 105, 0.3));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
}

.user-card:hover {
    transform: translateY(-8px);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.user-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    min-width: 0;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    margin-right: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
    min-width: 60px;
    min-height: 60px;
    overflow: hidden;
}

.user-info h3 {
    color: #ffffff;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    word-break: break-word;
    max-width: 180px;
}

.user-role {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-vendedor {
    background: rgba(72, 219, 251, 0.2);
    color: #48dbfb;
    border: 1px solid rgba(72, 219, 251, 0.3);
}

.role-jefe {
    background: rgba(254, 202, 87, 0.2);
    color: #feca57;
    border: 1px solid rgba(254, 202, 87, 0.3);
}

.user-details {
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.detail-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

.detail-value {
    color: #ffffff;
    font-weight: 500;
    font-size: 0.9rem;
}

.user-actions {
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
    display: flex;
    align-items: center;
    gap: 0.25rem;
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
    .controls-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .search-filters {
        flex-direction: column;
    }
    
    .filter-input, .filter-select {
        min-width: auto;
        width: 100%;
    }
    
    .users-grid {
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

<div class="vendedores-container">
    <!-- Header -->
    <div class="vendedores-header">
        <h1 class="vendedores-title">Gestión de Vendedores</h1>
        <p style="color: rgba(255, 255, 255, 0.7); margin: 0;">
            Administra el personal de ventas y controla los accesos al sistema
        </p>
    </div>

    <!-- Estadísticas Overview -->
    <div class="stats-overview">
        <div class="overview-card">
            <div class="overview-icon total">👥</div>
            <div class="overview-value"><?= $stats['total_usuarios'] ?></div>
            <div class="overview-label">Total Usuarios</div>
        </div>
        
        <div class="overview-card">
            <div class="overview-icon vendedores">🛍️</div>
            <div class="overview-value"><?= $stats['vendedores'] ?></div>
            <div class="overview-label">Vendedores</div>
        </div>
        
        <div class="overview-card">
            <div class="overview-icon jefes">👑</div>
            <div class="overview-value"><?= $stats['jefes'] ?></div>
            <div class="overview-label">Jefes</div>
        </div>
        
        <div class="overview-card">
            <div class="overview-icon nuevos">✨</div>
            <div class="overview-value"><?= $stats['nuevos_hoy'] ?></div>
            <div class="overview-label">Nuevos Hoy</div>
        </div>
    </div>

    <!-- Controles -->
    <div class="controls-section">
        <form method="GET" class="controls-grid">
            <div class="search-filters">
                <input type="text" name="search" class="filter-input" 
                       placeholder="Buscar por nombre, correo o RUT..." 
                       value="<?= htmlspecialchars($search) ?>">
                <select name="rol" class="filter-select">
                    <option value="">Todos los roles</option>
                    <option value="vendedor" <?= $rol_filter === 'vendedor' ? 'selected' : '' ?>>Vendedores</option>
                    <option value="jefe" <?= $rol_filter === 'jefe' ? 'selected' : '' ?>>Jefes</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">
                <i class="bi bi-search"></i> Buscar
            </button>
            <button type="button" class="btn-primary" onclick="openCreateModal()">
                <i class="bi bi-person-plus"></i> Nuevo Usuario
            </button>
        </form>
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

    <!-- Grid de usuarios -->
    <div class="users-grid">
        <?php foreach ($usuarios as $usuario): ?>
            <div class="user-card">
                <div class="user-header">
                    <div class="user-avatar">
                        <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <h3><?= htmlspecialchars($usuario['nombre']) ?></h3>
                        <span class="user-role role-<?= $usuario['rol'] ?>">
                            <?= ucfirst($usuario['rol']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="user-details">
                    <div class="detail-item">
                        <span class="detail-label">Correo:</span>
                        <span class="detail-value"><?= htmlspecialchars($usuario['correo']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">RUT:</span>
                        <span class="detail-value"><?= htmlspecialchars($usuario['rut']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Creado:</span>
                        <span class="detail-value"><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Último acceso:</span>
                        <span class="detail-value"><?= date('d/m/Y', strtotime($usuario['updated_at'])) ?></span>
                    </div>
                </div>
                
                <div class="user-actions">
                    <button class="btn-sm btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($usuario)) ?>)">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                    <button class="btn-sm btn-delete" onclick="confirmDelete(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nombre']) ?>')">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&rol=<?= urlencode($rol_filter) ?>" 
                   class="page-btn <?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para crear/editar usuario -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Nuevo Usuario</h2>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        
        <form id="userForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="userId">
            
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre Completo *</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="correo">Correo Electrónico *</label>
                <input type="email" name="correo" id="correo" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="rut">RUT *</label>
                <input type="text" name="rut" id="rut" class="form-control" required 
                       placeholder="12.345.678-9">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="rol">Rol *</label>
                <select name="rol" id="rol" class="form-control" required>
                    <option value="">Seleccionar rol</option>
                    <option value="vendedor">Vendedor</option>
                    <option value="jefe">Jefe</option>
                </select>
            </div>
            
            <div class="form-group" id="contrasenaGroup">
                <label class="form-label" for="contrasena">Contraseña *</label>
                <input type="password" name="contrasena" id="contrasena" class="form-control">
            </div>
            
            <div class="form-group" id="nuevaContrasenaGroup" style="display: none;">
                <label class="form-label" for="nueva_contrasena">Nueva Contraseña (dejar vacío para mantener actual)</label>
                <input type="password" name="nueva_contrasena" id="nueva_contrasena" class="form-control">
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
            ¿Estás seguro de que deseas eliminar al usuario "<span id="deleteUserName"></span>"?
            Esta acción no se puede deshacer y se eliminarán todos los datos asociados.
        </p>
        
        <form id="deleteForm" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteUserId">
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button type="submit" class="btn-delete">Eliminar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nuevo Usuario';
    document.getElementById('formAction').value = 'create';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('contrasenaGroup').style.display = 'block';
    document.getElementById('nuevaContrasenaGroup').style.display = 'none';
    document.getElementById('contrasena').required = true;
    document.getElementById('userModal').style.display = 'block';
}

function openEditModal(usuario) {
    document.getElementById('modalTitle').textContent = 'Editar Usuario';
    document.getElementById('formAction').value = 'update';
    document.getElementById('userId').value = usuario.id;
    document.getElementById('nombre').value = usuario.nombre;
    document.getElementById('correo').value = usuario.correo;
    document.getElementById('rut').value = usuario.rut;
    document.getElementById('rol').value = usuario.rol;
    document.getElementById('contrasenaGroup').style.display = 'none';
    document.getElementById('nuevaContrasenaGroup').style.display = 'block';
    document.getElementById('contrasena').required = false;
    document.getElementById('userModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

function confirmDelete(id, nombre) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserName').textContent = nombre;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const userModal = document.getElementById('userModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target == userModal) {
        userModal.style.display = 'none';
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
    
    // Formatear RUT while typing
    const rutInput = document.getElementById('rut');
    rutInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9kK]/g, '');
        if (value.length > 1) {
            let rut = value.slice(0, -1);
            let dv = value.slice(-1);
            rut = rut.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            e.target.value = rut + '-' + dv;
        }
    });
});
</script>
