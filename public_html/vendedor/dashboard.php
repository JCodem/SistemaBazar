<?php
require_once '../../includes/middleware_unificado.php';
middlewareVendedor();
// Redirigir directamente al Punto de Venta
header('Location: ../modules/pos/index.php');
exit;
?>