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
        $debug_error = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => 'Usuario no encontrado',
            'correo_buscado' => $correo
        ];
        file_put_contents('../debug_auth.log', 
            "ERROR: " . json_encode($debug_error, JSON_PRETTY_PRINT) . "\n", 
            FILE_APPEND | LOCK_EX);
        echo "<script>console.log('AUTH ERROR:', " . json_encode($debug_error) . ");</script>";
        
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

        // Debug - Crear archivo de log y consola
        $debug_info = [
            'timestamp' => date('Y-m-d H:i:s'),
            'usuario_id' => $usuario['id'],
            'usuario_nombre' => $usuario['nombre'],
            'usuario_rol' => $usuario['rol'],
            'correo' => $correo
        ];
        
        file_put_contents('../debug_auth.log', 
            json_encode($debug_info, JSON_PRETTY_PRINT) . "\n", 
            FILE_APPEND | LOCK_EX);
        
        // Redireccionar según el rol ANTES de cualquier output
        if ($usuario['rol'] === 'jefe'){
            $_SESSION['mensaje'] = "Redirigiendo a admin...";
            file_put_contents('../debug_auth.log', 
                "REDIRECT: Redirigiendo a admin/dashboard.php\n", 
                FILE_APPEND | LOCK_EX);
            header('Location: admin/dashboard.php');
            exit;
        } else {
            $_SESSION['mensaje'] = "Redirigiendo a vendedor...";
            file_put_contents('../debug_auth.log', 
                "REDIRECT: Redirigiendo a vendedor/dashboard.php\n", 
                FILE_APPEND | LOCK_EX);
            header('Location: vendedor/dashboard.php');
            exit;
        }
    } else {
        $debug_password_error = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => 'Contraseña incorrecta',
            'correo' => $correo,
            'hash_en_db' => substr($usuario['contrasena'], 0, 20) . '...'
        ];
        file_put_contents('../debug_auth.log', 
            "PASSWORD ERROR: " . json_encode($debug_password_error, JSON_PRETTY_PRINT) . "\n", 
            FILE_APPEND | LOCK_EX);
        echo "<script>console.log('PASSWORD ERROR:', " . json_encode($debug_password_error) . ");</script>";
        
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
