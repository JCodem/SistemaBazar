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

// Renderizar menú de usuario y POS
echo '<div style="width:100%;display:flex;justify-content:flex-end;align-items:center;gap:1rem;padding:1.5rem 2rem 0 2rem;position:relative;z-index:2001;">';
echo '<div style="position:relative;">';
echo '<button id="userMenuBtn" style="background:#fff;border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,0.08);cursor:pointer;">'.strtoupper(substr($usuario,0,1)).'</button>';
echo '<span style="margin-left:0.75rem;font-weight:500;">'.htmlspecialchars($usuario).'</span>';
echo '<div id="userMenuDropdown" style="display:none;position:absolute;right:0;top:54px;background:#222b3a;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.15);min-width:180px;z-index:2002;">';
echo '<a href="/SistemaBazar/public_html/logout.php" style="display:flex;align-items:center;gap:0.5rem;padding:1rem;color:#ff6b6b;text-decoration:none;border-radius:12px;font-weight:500;"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<script>
document.addEventListener("DOMContentLoaded",function(){
  var btn=document.getElementById("userMenuBtn");
  var menu=document.getElementById("userMenuDropdown");
  btn.addEventListener("click",function(e){
    e.stopPropagation();
    menu.style.display=menu.style.display==="block"?"none":"block";
  });
  document.addEventListener("click",function(e){
    if(menu.style.display==="block"&&!menu.contains(e.target)&&e.target!==btn){menu.style.display="none";}
  });
});
</script>';

$posModule = new POSModule($conn);
$posModule->initialize()->renderPOS();

if (esVendedor()) {
    require_once BASE_PATH . '/includes/footer_vendedor.php';
} else {
    require_once BASE_PATH . '/includes/footer_admin.php';
}
?>
