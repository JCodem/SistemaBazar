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
  <!-- POS CSS -->
  <link rel="stylesheet" href="assets/css/pos.css">
  <!-- Layout Vendedor CSS - DEBE IR AL FINAL PARA SOBRESCRIBIR BOOTSTRAP -->
  <link rel="stylesheet" href="/assets/css/layout_vendedor.css">
</head>
<body>

<!-- Botón toggle para la sidebar -->
<button class="sidebar-toggle" id="sidebarToggle">
  <i class="bi bi-list" style="font-size: 1.2rem;"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <!-- Header del usuario -->

  <div class="sidebar-header" style="position:relative;">
    <div class="user-avatar" id="userMenuToggle" style="cursor:pointer;">
      <?= strtoupper(substr($nombre, 0, 1)) ?>
    </div>
    <h4 class="user-name" id="userMenuToggleName" style="cursor:pointer;"><?= $nombre ?></h4>
    <p class="user-role">Vendedor</p>
    <!-- Dropdown menu -->
    <div id="userDropdown" style="display:none; position:absolute; top:90px; left:50%; transform:translateX(-50%); background:#222b3a; border-radius:12px; box-shadow:0 4px 16px rgba(0,0,0,0.15); min-width:180px; z-index:2000;">
      <a href="/logout.php" class="nav-link logout-link" style="display:flex; align-items:center; gap:0.5rem; padding:1rem; color:#ff6b6b; text-decoration:none; border-radius:12px;">
        <i class="bi bi-box-arrow-right nav-icon"></i>
        <span class="nav-text">Cerrar Sesión</span>
      </a>
    </div>
  </div>

  <!-- Navegación principal -->
  <div class="sidebar-nav">
    <div class="nav-section">
      <div class="nav-section-title">Sistema Bazar</div>
      
      <div class="nav-item">
        <a href="../modules/pos/" class="nav-link <?= in_array($current_page, ['dashboard.php', 'pos.php', 'index.php']) ? 'active' : '' ?>">
          <i class="bi bi-cart3 nav-icon"></i>
          <span class="nav-text">Punto de Venta</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Sección de logout (oculta, ahora en el menú de usuario) -->
  <!-- <div class="logout-section">
    <div class="nav-item">
      <a href="/logout.php" class="nav-link logout-link">
        <i class="bi bi-box-arrow-right nav-icon"></i>
        <span class="nav-text">Cerrar Sesión</span>
      </a>
    </div>
  </div> -->
</div>

<!-- Inicio del contenido -->
<div class="main-content" id="mainContent">
  <div class="content-wrapper">

<script>
// Menú de usuario dropdown
document.addEventListener('DOMContentLoaded', function() {
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userMenuToggleName = document.getElementById('userMenuToggleName');
    const userDropdown = document.getElementById('userDropdown');
    function toggleUserDropdown(e) {
        e.stopPropagation();
        userDropdown.style.display = (userDropdown.style.display === 'block') ? 'none' : 'block';
    }
    userMenuToggle.addEventListener('click', toggleUserDropdown);
    userMenuToggleName.addEventListener('click', toggleUserDropdown);
    document.addEventListener('click', function(e) {
        if (userDropdown.style.display === 'block' && !userDropdown.contains(e.target) && e.target !== userMenuToggle && e.target !== userMenuToggleName) {
            userDropdown.style.display = 'none';
        }
    });

    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const sidebarToggle = document.getElementById('sidebarToggle');
    let sidebarVisible = window.innerWidth > 768;

    // Toggle sidebar
    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            // Mobile: Show/hide sidebar
            sidebar.classList.toggle('show');
        } else {
            // Desktop: Collapse/expand sidebar
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            sidebarToggle.classList.toggle('collapsed');
            
            // Cambiar icono del botón
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'bi bi-arrow-right';
            } else {
                icon.className = 'bi bi-list';
            }
        }
    }

    // Event listener para el botón toggle
    sidebarToggle.addEventListener('click', toggleSidebar);

    // Cerrar sidebar en mobile cuando se hace clic fuera
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Manejar resize de ventana
    window.addEventListener('resize', function() {
        const newWidth = window.innerWidth;
        
        if (newWidth > 768 && !sidebarVisible) {
            // Cambio de mobile a desktop
            sidebar.classList.remove('show');
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            sidebarToggle.classList.remove('collapsed');
            sidebarVisible = true;
        } else if (newWidth <= 768 && sidebarVisible) {
            // Cambio de desktop a mobile
            sidebar.classList.remove('collapsed', 'show');
            mainContent.classList.remove('expanded');
            sidebarToggle.classList.add('collapsed');
            sidebarVisible = false;
        }
    });

    // Mostrar/ocultar botón toggle según el tamaño de pantalla
    function updateToggleVisibility() {
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
