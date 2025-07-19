<?php
// Usar el nuevo archivo de configuración para rutas consistentes
require_once dirname(__DIR__, 3) . '/includes/config.php';

// Incluir los archivos necesarios usando BASE_PATH
require_once BASE_PATH . '/includes/middleware_unificado.php';
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/public_html/modules/pos/POSModule.php';

use Modules\POS\POSModule;

// Aplicar middleware para permitir acceso a vendedores y jefes
middlewareAmbos();

// Determinar layout según rol
if (esVendedor()) {
    $sesionStmt = $conn->prepare(
        "SELECT id FROM sesiones_caja WHERE usuario_id = ? AND estado = 'abierta' LIMIT 1"
    );
    $sesionStmt->execute([$_SESSION['user_id']]);
    if (!$sesionStmt->fetch(PDO::FETCH_ASSOC)) {
        require_once BASE_PATH . '/includes/layout_vendedor.php';
        echo '<div class="content-wrapper"><div class="alert alert-danger">Caja cerrada. No puede realizar ventas.</div></div>';
        require_once BASE_PATH . '/includes/footer_vendedor.php';
        exit;
    }
    require_once BASE_PATH . '/includes/layout_vendedor.php';
} else {
    require_once BASE_PATH . '/includes/layout_admin.php';
}


// Obtener nombre de usuario
$usuario = $_SESSION['usuario']['nombre'] ?? ($_SESSION['user_nombre'] ?? 'Usuario');



$posModule = new POSModule($conn);
$posModule->initialize()->renderPOS();

if (esVendedor()) {
    require_once BASE_PATH . '/includes/footer_vendedor.php';
} else {
    require_once BASE_PATH . '/includes/footer_admin.php';
}
?>
