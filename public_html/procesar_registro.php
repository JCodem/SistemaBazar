<?php
session_start();
require_once '../includes/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Capturar los datos del formulario
$correo = $_POST['correo'] ?? '';
$contrasena = $_POST['contrasena'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$rut = $_POST['rut'] ?? '';
$rol = $_POST['rol'] ?? '';

try {
    // Verifica si ya existe el correo
    $verificar = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
    $verificar->execute([':correo' => $correo]);
    $resultado = $verificar->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        $_SESSION['registro_error'] = "Ese correo ya está registrado.";
        header('Location: register.php');
        exit;
    }

    // Hash de la contraseña
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Inserta el nuevo usuario
    $stmt = $conn->prepare("
        INSERT INTO usuarios (correo, contrasena, nombre, rut, rol, created_at, updated_at)
        VALUES (:correo, :contrasena, :nombre, :rut, :rol, NOW(), NOW())
    ");
    $stmt->execute([
        ':correo' => $correo,
        ':contrasena' => $hash,
        ':nombre' => $nombre,
        ':rut' => $rut,
        ':rol' => $rol
    ]);

    $_SESSION['registro_exito'] = "Usuario registrado correctamente.";
    header('Location: register.php');
    exit;

} catch (Exception $e) {
    $_SESSION['registro_error'] = "Error: " . $e->getMessage();
    header('Location: register.php');
    exit;
}
