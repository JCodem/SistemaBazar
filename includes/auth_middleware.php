
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    escribir_log("AUTH_MIDDLEWARE: Acceso denegado, sesión no iniciada");
    header('Location: /public_html/login.php');
    exit;
} else {
    escribir_log("AUTH_MIDDLEWARE: Acceso permitido, user_id: " . $_SESSION['user_id']);
}
function escribir_log($mensaje) {
    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $linea = "[$fecha] [$ip] $mensaje\n";
    file_put_contents(__DIR__ . '/log.txt', $linea, FILE_APPEND);
}