<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']['rol']) || $_SESSION['usuario']['rol'] !== 'jefe') {
    header('Location: ../public_html/login.php');
    exit;
}
