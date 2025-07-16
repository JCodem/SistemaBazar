<?php
// Iniciar sesión y verificar autenticación
session_start();

// Verificar autenticación - compatibilidad con ambos formatos de sesión
if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
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
    header('Location: ../login.php?error=acceso_no_autorizado');
    exit;
}

// Incluir archivos necesarios
require_once '../../includes/db.php'; // Este archivo crea $conn directamente
require_once '../../modules/pos/POSModule.php';

use Modules\POS\POSModule;

// Usar la conexión existente ($conn) creada en db.php
// No necesitamos crear una nueva instancia de Database

// Inicializar el módulo POS con la conexión existente
$posModule = new POSModule($conn);
$posModule->initialize();

// Mostrar la interfaz del POS
$posModule->renderPOS();
?>
