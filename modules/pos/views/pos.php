<?php
// Verificar si el usuario está autenticado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /SistemaBazar/public_html/login.php');
    exit;
}

// Usar las variables de sesión user_nombre si están disponibles, sino usar usuario['nombre']
if (isset($_SESSION['user_nombre'])) {
    $nombre = htmlspecialchars($_SESSION['user_nombre']);
} elseif (isset($_SESSION['usuario']['nombre'])) {
    $nombre = htmlspecialchars($_SESSION['usuario']['nombre']);
} else {
    $nombre = 'Vendedor';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Punto de Venta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="/SistemaBazar/modules/pos/assets/css/pos.css">
  <style>
    body {
      min-height: 100vh;
      background-color: #f8f9fa;
    }
    .navbar {
      background-color: #343a40;
      padding: 0.5rem 1rem;
    }
    .navbar-brand {
      color: white;
      font-weight: bold;
    }
    .navbar .btn-outline-light:hover {
      background-color: #f8f9fa;
      color: #343a40;
    }
  </style>
</head>
<body>

<!-- Barra de navegación simple para el POS -->
<nav class="navbar navbar-dark mb-3">
  <div class="container-fluid">
    <span class="navbar-brand">
      <i class="bi bi-cart4"></i> Sistema POS
    </span>
    <div class="d-flex">
      <span class="text-light me-3">
        <i class="bi bi-person-circle"></i> <?= $nombre ?>
      </span>
      <a href="/SistemaBazar/public_html/vendedor/dashboard.php" class="btn btn-outline-light btn-sm me-2">
        <i class="bi bi-house"></i> Panel
      </a>
      <a href="/SistemaBazar/public_html/logout.php" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-box-arrow-right"></i> Salir
      </a>
    </div>
  </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Panel principal: Búsqueda y Carrito Unificado -->
        <div class="col-lg-8 pt-3">
            <?php include_once __DIR__ . '/components/cart.php'; ?>
        </div>
        
        <!-- Panel derecho: Finalizar Compra -->
        <div class="col-lg-4 pt-3">
            <?php include_once __DIR__ . '/components/payment-options.php'; ?>
        </div>
    </div>
</div>

<!-- Modal para recibo -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recibo de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="receipt-content">
                <!-- El contenido del recibo se cargará aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="print-receipt">Imprimir</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast container para notificaciones -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<!-- Scripts JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/SistemaBazar/modules/pos/assets/js/pos.js"></script>
<script src="/SistemaBazar/modules/pos/assets/js/payment-handler.js"></script>

<footer class="bg-dark text-white text-center py-2 mt-5">
  <div class="container">
    <p class="mb-0">&copy; <?= date('Y') ?> Sistema de Punto de Venta - Todos los derechos reservados</p>
  </div>
</footer>

</body>
</html>
