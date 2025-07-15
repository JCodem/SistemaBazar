<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']['rol']) || $_SESSION['usuario']['rol'] !== 'vendedor') {
    header('Location: ../login.php');
    exit;
}
