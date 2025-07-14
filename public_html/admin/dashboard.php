<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'jefe') {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Jefe de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="text-center">¡Bienvenido, <?= $_SESSION['usuario']['nombre'] ?? 'Jefe' ?>!</h2>
    <p class="text-center">Estás en el <strong>panel del jefe de ventas</strong>.</p>
    <div class="text-center mt-4">
        <a href="../logout.php" class="btn btn-danger">Cerrar sesión</a>
    </div>
</div>

</body>
</html>
