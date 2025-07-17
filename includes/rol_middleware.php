<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Permitir acceso si el rol es jefe o admin en cualquiera de los formatos de sesión
$rol = null;
if (isset($_SESSION['usuario']['rol'])) {
    $rol = $_SESSION['usuario']['rol'];
} elseif (isset($_SESSION['user_rol'])) {
    $rol = $_SESSION['user_rol'];
}
if (!$rol || !in_array($rol, ['jefe', 'admin'])) {
    escribir_log("ROL_MIDDLEWARE: Acceso denegado. Rol detectado: " . var_export($rol, true) . " | SESSION: " . json_encode($_SESSION));
    $currentScript = basename($_SERVER['SCRIPT_NAME']);
    if ($currentScript !== 'login.php') {
        header('Location: ../public_html/login.php?error=acceso_no_autorizado');
        exit;
    }
} else {
    escribir_log("ROL_MIDDLEWARE: Acceso permitido. Rol detectado: " . var_export($rol, true) . " | SESSION: " . json_encode($_SESSION));
}

if (!function_exists('escribir_log')) {
    function escribir_log($mensaje) {
        $fecha = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $linea = "[$fecha] [$ip] $mensaje\n";
        file_put_contents(__DIR__ . '/log.txt', $linea, FILE_APPEND);
    }
}
