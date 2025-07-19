<?php
require_once 'middleware_unificado.php';
middlewareVendedor();

// Obtener información del usuario actual
$usuario = obtenerUsuarioActual();
$nombre = $usuario['nombre'] ?? 'Vendedor';

// Obtener la página actual para resaltar el enlace activo
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= $titulo ?? 'Panel del Vendedor' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Layout Vendedor CSS - DEBE IR AL FINAL PARA SOBRESCRIBIR BOOTSTRAP -->

</head>
<body>





<!-- Inicio del contenido -->
<div class="main-content" id="mainContent">
  <div class="content-wrapper">

<script>
// Menú de usuario dropdown
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown usuario
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenuToggleName = document.getElementById('userMenuToggleName');
    const userDropdown = document.getElementById('userDropdown');
    function toggleUserDropdown(e) {
        e.stopPropagation();
        if (userDropdown) {
            userDropdown.style.display = (userDropdown.style.display === 'block') ? 'none' : 'block';
        }
    }
    if (userMenuToggle) userMenuToggle.addEventListener('click', toggleUserDropdown);
    if (userMenuToggleName) userMenuToggleName.addEventListener('click', toggleUserDropdown);
    document.addEventListener('click', function(e) {
        if (userDropdown && userDropdown.style.display === 'block' && !userDropdown.contains(e.target) && e.target !== userMenuToggle && e.target !== userMenuToggleName) {
            userDropdown.style.display = 'none';
        }
    });

    // Sidebar
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    let sidebarVisible = window.innerWidth > 768;

    function toggleSidebar() {
        if (!sidebar || !sidebarToggle || !mainContent) return;
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            sidebarToggle.classList.toggle('collapsed');
            const icon = sidebarToggle.querySelector('i');
            if (icon) {
                if (sidebar.classList.contains('collapsed')) {
                    icon.className = 'bi bi-arrow-right';
                } else {
                    icon.className = 'bi bi-list';
                }
            }
        }
    }
    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);

    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && sidebarToggle) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    window.addEventListener('resize', function() {
        const newWidth = window.innerWidth;
        if (!sidebar || !sidebarToggle || !mainContent) return;
        if (newWidth > 768 && !sidebarVisible) {
            sidebar.classList.remove('show');
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            sidebarToggle.classList.remove('collapsed');
            sidebarVisible = true;
        } else if (newWidth <= 768 && sidebarVisible) {
            sidebar.classList.remove('collapsed', 'show');
            mainContent.classList.remove('expanded');
            sidebarToggle.classList.add('collapsed');
            sidebarVisible = false;
        }
    });

    function updateToggleVisibility() {
        if (!sidebarToggle) return;
        if (window.innerWidth <= 768) {
            sidebarToggle.classList.add('visible', 'collapsed');
        } else {
            sidebarToggle.classList.add('visible');
            sidebarToggle.classList.remove('collapsed');
        }
    }
    updateToggleVisibility();
    window.addEventListener('resize', updateToggleVisibility);

    // Animación suave para los enlaces
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(8px)';
        });
        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(0)';
            }
        });
    });
});
</script>
