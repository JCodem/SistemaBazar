<?php
session_start();
require_once '../includes/db.php';

$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contraseña'] ?? ''; // Mantener 'contraseña' con tilde como en el formulario

try {
    $query = $conn->prepare("SELECT * FROM usuarios WHERE correo = ? LIMIT 1");
    $query->bind_param("s", $correo);
    $query->execute();
    $resultado = $query->get_result();
    $usuario = $resultado->fetch_assoc();

    // Agregar depuración para ver qué está fallando
    if (!$usuario) {
        $_SESSION['error'] = 'Usuario no encontrado con ese correo';
        header('Location: login.php');
        exit;
    }
    
    // Añadir depuración para ver valores
    $_SESSION['debug_info'] = [
        'hash_almacenado' => $usuario['contrasena'],
        'rol' => $usuario['rol'],
        'verificacion' => password_verify($contraseña, $usuario['contrasena']) ? 'Éxito' : 'Fallido'
    ];
    
    // Verificar contraseña
    if (password_verify($contraseña, $usuario['contrasena'])) {
        // Guardar información del usuario en la sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nombre'] = $usuario['nombre'];
        $_SESSION['user_rol'] = $usuario['rol'];

        // Redireccionar según el rol - Usar rutas absolutas
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
