<?php
session_start();
require_once '../includes/db.php';

$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contraseña'] ?? '';

try {
    $query = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
    $query->bindParam(':correo', $correo);
    $query->execute();

    $usuario = $query->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($contraseña, $usuario['contraseña'])) {
        $_SESSION['usuario'] = $usuario;

        if ($usuario['rol'] === 'jefe') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: vendedor/venta.php');
        }
        exit;
    } else {
        $_SESSION['error'] = 'Credenciales incorrectas';
        header('Location: login.php');
        exit;
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

 

?>
