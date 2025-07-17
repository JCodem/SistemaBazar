<?php
// Interfaz del vendedor con layout y POS embebido
require_once '../../includes/layout_vendedor.php';
require_once '../../includes/db.php';
require_once '../../modules/pos/POSModule.php';

use Modules\POS\POSModule;

// Inicializar y renderizar POS
$posModule = new POSModule($conn);
$posModule->initialize();
?>
<div class="content-wrapper">
  <?php $posModule->renderPOS(); ?>
</div>
<?php
// Incluir footer del vendedor
require_once '../../includes/footer_vendedor.php';
?>
<?php
require_once '../../includes/middleware_unificado.php';
middlewareVendedor();
// Redirigir directamente al Punto de Venta
header('Location: ../modules/pos/index.php');
exit;
?>