<?php
session_start();

// Verificar autenticación - compatibilidad con ambos formatos de sesión
if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario'])) {
    header('Location: ../../public_html/login.php');
    exit;
}

// Verificar rol - compatibilidad con ambos formatos de sesión
$tieneAcceso = false;

if (isset($_SESSION['user_rol'])) {
    // Nuevo formato de sesión
    $tieneAcceso = in_array($_SESSION['user_rol'], ['vendedor', 'jefe', 'admin']);
} elseif (isset($_SESSION['rol'])) {
    // Formato de sesión con ['rol']
    $tieneAcceso = in_array($_SESSION['rol'], ['vendedor', 'jefe', 'admin']);
} elseif (isset($_SESSION['usuario']['rol'])) {
    // Formato antiguo de sesión
    $tieneAcceso = in_array($_SESSION['usuario']['rol'], ['vendedor', 'jefe', 'admin']);
}

if (!$tieneAcceso) {
    header('Location: ../../public_html/login.php?error=acceso_no_autorizado');
    exit;
}

// Incluir archivos necesarios
require_once '../../includes/db.php';
require_once 'POSModule.php';

use Modules\POS\POSModule;

// Usar la conexión existente ($conn) creada en db.php

// Inicializar el módulo POS
$posModule = new POSModule($conn);
$posModule->initialize();

// Mostrar la interfaz del POS
$posModule->renderPOS();
?>
