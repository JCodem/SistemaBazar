
<?php
session_start();
require_once '../includes/db.php';

$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contrasena'] ?? ''; // Mantener 'contraseña' con tilde como en el formulario

try {
    // Usar PDO con parámetros nombrados
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmt->execute(['correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Agregar depuración para ver qué está fallando
    if (!$usuario) {
        escribir_log("LOGIN ERROR: Usuario no encontrado - Correo: $correo");
        $_SESSION['error'] = 'Usuario no encontrado con ese correo';
        header('Location: login.php');
        exit;
    }
    
    // Verificar contraseña
    if (password_verify($contraseña, $usuario['contrasena'])) {
        // Guardar información del usuario en la sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nombre'] = $usuario['nombre'];
        $_SESSION['user_rol'] = $usuario['rol'];
        escribir_log("LOGIN OK: Usuario ID {$usuario['id']} ({$usuario['correo']}) - Rol: {$usuario['rol']} - SESSION: " . json_encode($_SESSION));
        // Depuración extra: loguear el estado antes de redirigir
        if ($usuario['rol'] === 'jefe' || $usuario['rol'] === 'admin') {
            escribir_log("REDIRECT: Usuario con rol '{$usuario['rol']}' será enviado a admin/dashboard.php");
            $_SESSION['mensaje'] = "Redirigiendo a admin...";
            header('Location: admin/dashboard.php');
        } else {
            escribir_log("REDIRECT: Usuario con rol '{$usuario['rol']}' será enviado a vendedor/dashboard.php");
            $_SESSION['mensaje'] = "Redirigiendo a vendedor...";
            header('Location: vendedor/dashboard.php');
        }
        exit;
    } else {
        escribir_log("LOGIN ERROR: Credenciales incorrectas para correo $correo");
        $_SESSION['error'] = 'Credenciales incorrectas';
        header('Location: login.php');
        exit;
    }

} catch (Exception $e) {
    escribir_log("LOGIN ERROR: Excepción para correo $correo - " . $e->getMessage());
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header('Location: login.php');
    exit;
}

 // Función para escribir logs

function escribir_log($mensaje) {
    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $linea = "[$fecha] [$ip] $mensaje\n";
    file_put_contents(__DIR__ . '/../includes/log.txt', $linea, FILE_APPEND);
}

?>

