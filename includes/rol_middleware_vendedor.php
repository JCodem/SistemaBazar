<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'vendedor') {
    header('Location: ../public_html/login.php?error=acceso_no_autorizado');
    exit;
}
