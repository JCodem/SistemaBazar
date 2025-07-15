<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuario = $_SESSION['usuario'] ?? [];
$nombre = htmlspecialchars($usuario['nombre'] ?? 'Vendedor');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= $titulo ?? 'Panel del Vendedor' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
    }
    .sidebar {
      width: 250px;
      background-color: #343a40;
      color: white;
      flex-shrink: 0;
      padding-top: 1rem;
    }
    .sidebar a {
      color: white;
      padding: 10px 20px;
      display: block;
      text-decoration: none;
    }
    .sidebar a:hover {
      background-color: #495057;
    }
    .main-content {
      flex-grow: 1;
      background-color: #f8f9fa;
      padding: 2rem;
    }
    .card:hover {
      transform: scale(1.02);
      transition: all 0.2s ease-in-out;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <h4 class="text-center mb-4">👤 <?= $nombre ?></h4>
  <a href="dashboard.php">🏠 Panel</a>
  <a href="registrar_venta.php">📝 Registrar Venta</a>
  <a href="historial_ventas.php">📊 Historial de Ventas</a>
  <a href="perfil.php">👤 Perfil</a>
  <a href="descargar_reporte.php">📥 Reporte Diario</a>
  <a href="../logout.php" class="text-danger">🚪 Cerrar Sesión</a>
</div>

<!-- Inicio del contenido -->
<div class="main-content">
