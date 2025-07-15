<?php
session_start();
require_once '../includes/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Capturar los datos del formulario
$correo = $_POST['correo'] ?? '';
$contraseña = $_POST['contraseña'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$rut = $_POST['rut'] ?? '';
$rol = $_POST['rol'] ?? '';

try {
    // Verifica si ya existe el correo
    $verificar = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
    $verificar->bindParam(':correo', $correo);
    $verificar->execute();

    if ($verificar->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['registro_error'] = "Ese correo ya está registrado.";
        header('Location: register.php');
        exit;
    }

    // Hash de la contraseña
    $hash = password_hash($contraseña, PASSWORD_DEFAULT);

    // Inserta el nuevo usuario
    $stmt = $conn->prepare("
        INSERT INTO usuarios (correo, contraseña, nombre, rut, rol, created_at, updated_at)
        VALUES (:correo, :contrasena, :nombre, :rut, :rol, NOW(), NOW())
    ");

    $stmt->bindParam(':correo', $correo);
    $stmt->bindParam(':contrasena', $hash); // usa :contrasena (no :contraseña por compatibilidad)
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':rut', $rut);
    $stmt->bindParam(':rol', $rol);

    $stmt->execute();

    $_SESSION['registro_exito'] = "Usuario registrado correctamente.";
    header('Location: register.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['registro_error'] = "Error: " . $e->getMessage();
    header('Location: register.php');
    exit;
}
