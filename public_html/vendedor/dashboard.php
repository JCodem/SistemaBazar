<?php
require_once '../../includes/auth_middleware.php';
require_once '../../includes/rol_middleware_vendedor.php';
$titulo = 'Dashboard del Vendedor';
include '../../includes/layout_vendedor.php';
?>

<h2 class="mb-4">Panel del Vendedor</h2>
<p class="mb-4">Aquí puedes gestionar tus ventas y revisar tu información.</p>

<div class="row g-4">
  <div class="col-md-6 col-lg-4">
    <a href="registrar_venta.php" class="text-decoration-none text-dark">
      <div class="card p-3 h-100">
        <h5 class="card-title">📝 Registrar Venta</h5>
        <p class="card-text">Agregar una nueva venta al sistema.</p>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-4">
    <a href="historial_ventas.php" class="text-decoration-none text-dark">
      <div class="card p-3 h-100">
        <h5 class="card-title">📊 Historial de Ventas</h5>
        <p class="card-text">Consulta tus ventas anteriores con detalle.</p>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-4">
    <a href="perfil.php" class="text-decoration-none text-dark">
      <div class="card p-3 h-100">
        <h5 class="card-title">👤 Perfil</h5>
        <p class="card-text">Editar tus datos personales.</p>
      </div>
    </a>
  </div>
  <div class="col-md-6 col-lg-4">
    <a href="descargar_reporte.php" class="text-decoration-none text-dark">
      <div class="card p-3 h-100">
        <h5 class="card-title">📥 Reporte Diario</h5>
        <p class="card-text">Descarga un resumen de tus ventas del día.</p>
      </div>
    </a>
  </div>
</div>

<?php include '../../includes/footer_vendedor.php'; ?>
