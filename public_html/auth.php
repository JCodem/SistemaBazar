<?php
session_start();
require_once '../includes/db.php';

$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contrasena'] ?? ''; // Mantener 'contraseña' con tilde como en el formulario

try {

// Usar PDO con parámetros nombrados
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
$stmt->execute([':correo' => $correo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Agregar depuración para ver qué está fallando
    if (!$usuario) {
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

        // Redireccionar según el rol - Usar rutas relativas
        if ($usuario['rol'] === 'jefe' || $usuario['rol'] === 'admin') {
            $_SESSION['mensaje'] = "Redirigiendo a admin...";
            header('Location: admin/dashboard.php');
        } else {
            $_SESSION['mensaje'] = "Redirigiendo a vendedor...";
            header('Location: vendedor/dashboard.php');
        }
        exit;
    } else {
        $_SESSION['error'] = 'Credenciales incorrectas';
        header('Location: login.php');
        exit;
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header('Location: login.php');
    exit;
}

 

?>
