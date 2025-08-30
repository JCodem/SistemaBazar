
<?php
session_start();
require_once '../includes/db.php';
$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contrasena'] ?? ''; // Mantener 'contraseña' con tilde como en el formulario


// Detectar si es petición AJAX (fetch, XMLHttpRequest)
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
    $stmt->execute(['correo' => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        escribir_log("LOGIN ERROR: Usuario no encontrado - Correo: $correo");
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado con ese correo', 'debug' => 'No existe usuario']);
            exit;
        } else {
            $_SESSION['error'] = 'Usuario no encontrado con ese correo';
            header('Location: login.php');
            exit;
        }
    }

    if (password_verify($contraseña, $usuario['contrasena'])) {
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_nombre'] = $usuario['nombre'];
        $_SESSION['user_rol'] = $usuario['rol'];
        escribir_log("LOGIN OK: Usuario ID {$usuario['id']} ({$usuario['correo']}) - Rol: {$usuario['rol']} - SESSION: " . json_encode($_SESSION));
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'user' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'rol' => $usuario['rol'],
                'correo' => $usuario['correo']
            ], 'debug' => 'Login correcto']);
            exit;
        } else {
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
        }
    } else {
        escribir_log("LOGIN ERROR: Credenciales incorrectas para correo $correo");
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas', 'debug' => 'Password incorrecto']);
            exit;
        } else {
            $_SESSION['error'] = 'Credenciales incorrectas';
            header('Location: login.php');
            exit;
        }
    }

} catch (Exception $e) {
    escribir_log("LOGIN ERROR: Excepción para correo $correo - " . $e->getMessage());
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage(), 'debug' => 'Excepción']);
        exit;
    } else {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: login.php');
        exit;
    }
}

 // Función para escribir logs

function escribir_log($mensaje) {
    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $linea = "[$fecha] [$ip] $mensaje\n";
    // Log en archivo
    file_put_contents(__DIR__ . '/../log.txt', $linea, FILE_APPEND);
    // Log en consola (error_log)
    error_log($linea);
}

?>

