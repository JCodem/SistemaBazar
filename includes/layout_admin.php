<?php
session_start();
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/rol_middleware.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'jefe') {
    header('Location: ../admin/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
        }

        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 1rem 0;
            flex-shrink: 0;
        }

        .sidebar a {
            color: white;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .sidebar h4 {
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .content {
            flex-grow: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            body {
                flex-direction: column;
            }

            .content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center">👑 Admin</h4>
    <a href="dashboard.php">🏠 Inicio</a>
    <a href="../admin/productos.php">📦 Gestión de productos</a>
    <a href="../admin/ventas.php">🧾 Registro de ventas</a>
    <a href="../admin/informes.php">📈 Informes por día</a>
    <a href="../admin/vendedores.php">👥 Gestión de vendedores</a>
    <a href="../admin/apertura_cierre.php">📅 Cierre del día</a>
    <a href="../admin/seguridad.php">🛡️ Seguridad</a>
    <a href="../admin/perfil.php">👤 Perfil del administrador</a>
    <a href="../logout.php" class="text-danger">🚪 Cerrar sesión</a>
</div>

<div class="content">
