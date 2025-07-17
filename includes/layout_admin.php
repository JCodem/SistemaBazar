<?php
require_once 'middleware_unificado.php';
middlewareAdmin();

// Obtener información del usuario actual
$usuario = obtenerUsuarioActual();
$nombre = $usuario['nombre'] ?? 'Administrador';

// Obtener la página actual para resaltar el enlace activo
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= $titulo ?? 'Panel del Administrador' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-width: 280px;
      --sidebar-bg: linear-gradient(135deg, #2d1b69 0%, #11998e 50%, #0f0f0f 100%);
      --sidebar-hover: rgba(255, 255, 255, 0.1);
      --sidebar-active: rgba(255, 255, 255, 0.2);
      --text-primary: #ffffff;
      --text-secondary: rgba(255, 255, 255, 0.7);
      --shadow-sidebar: 0 0 30px rgba(0, 0, 0, 0.3);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      min-height: 100vh;
      display: flex;
      overflow-x: hidden;
      background-color: #f8fafc;
    }

    .sidebar {
      width: var(--sidebar-width);
      background: var(--sidebar-bg);
      color: var(--text-primary);
      flex-shrink: 0;
      padding: 0;
      box-shadow: var(--shadow-sidebar);
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: var(--transition);
      transform: translateX(0);
    }

    .sidebar.collapsed {
      transform: translateX(-100%);
    }

    .sidebar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
        radial-gradient(circle at 70% 60%, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
        radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
      pointer-events: none;
      animation: starGlow 8s ease-in-out infinite;
    }

    @keyframes starGlow {
      0%, 100% { opacity: 0.3; }
      50% { opacity: 0.6; }
    }

    .sidebar-header {
      padding: 2rem 1.5rem 1.5rem;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      position: relative;
    }

    .user-avatar {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #ff6b6b, #ffd93d);
      border-radius: 50%;
      margin: 0 auto 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      font-weight: 600;
      color: white;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .user-avatar::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
      transform: rotate(45deg);
      animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
      0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
      100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }

    .user-name {
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0;
      color: var(--text-primary);
    }

    .user-role {
      font-size: 0.85rem;
      color: var(--text-secondary);
      margin-top: 0.25rem;
    }

    .sidebar-nav {
      flex: 1;
      padding: 1rem 0;
      overflow-y: auto;
    }

    .nav-section {
      margin-bottom: 1.5rem;
    }

    .nav-section-title {
      padding: 0 1.5rem 0.5rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-secondary);
    }

    .nav-item {
      margin: 0.25rem 0.75rem;
      border-radius: 12px;
      overflow: hidden;
      transition: var(--transition);
    }

    .nav-link {
      color: var(--text-primary);
      padding: 0.875rem 1rem;
      display: flex;
      align-items: center;
      text-decoration: none;
      transition: var(--transition);
      position: relative;
      border-radius: 12px;
      font-weight: 500;
    }

    .nav-link:hover {
      background: var(--sidebar-hover);
      color: var(--text-primary);
      transform: translateX(4px);
    }

    .nav-link.active {
      background: var(--sidebar-active);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .nav-link.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: linear-gradient(to bottom, #ff6b6b, #feca57);
      border-radius: 0 4px 4px 0;
    }

    .nav-icon {
      font-size: 1.2rem;
      margin-right: 0.75rem;
      width: 20px;
      text-align: center;
      transition: var(--transition);
    }

    .nav-text {
      font-size: 0.95rem;
      font-weight: 500;
    }

    .nav-link:hover .nav-icon {
      transform: scale(1.1);
    }

    .logout-section {
      flex-shrink: 0;
      padding: 1rem 0.75rem 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      margin-top: auto;
    }

    .logout-link {
      color: #ff6b6b !important;
      background: rgba(255, 107, 107, 0.1);
      border: 1px solid rgba(255, 107, 107, 0.3);
    }

    .logout-link:hover {
      background: rgba(255, 107, 107, 0.2) !important;
      color: #ff5252 !important;
      transform: translateX(4px);
    }

    .main-content {
      flex-grow: 1;
      background: linear-gradient(135deg, #2d1b69 0%, #11998e 30%, #0f0f0f 100%);
      min-height: 100vh;
      position: relative;
      margin-left: var(--sidebar-width);
      transition: var(--transition);
      width: calc(100% - var(--sidebar-width));
    }

    .main-content.expanded {
      margin-left: 0;
      width: 100%;
    }

    /* Botón toggle para la sidebar */
    .sidebar-toggle {
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1001;
      background: rgba(15, 15, 25, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      color: white;
      padding: 10px;
      cursor: pointer;
      transition: var(--transition);
      backdrop-filter: blur(10px);
      display: block;
    }

    .sidebar-toggle:hover {
      background: rgba(25, 25, 35, 0.9);
      transform: scale(1.05);
    }

    .content-wrapper {
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
      min-height: calc(100vh - 4rem);
      box-sizing: border-box;
    }

    .card:hover {
      transform: translateY(-4px);
      transition: var(--transition);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    /* Prevenir solapamientos */
    body {
      overflow-x: hidden;
    }

    .sidebar, .main-content {
      box-sizing: border-box;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.show {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
        width: 100%;
      }

      .content-wrapper {
        padding: 4rem 1rem 1rem;
      }
    }

    @media (min-width: 769px) {
      .main-content {
        margin-left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width));
      }
      
      .sidebar-toggle {
        left: calc(var(--sidebar-width) + 20px);
        transition: left var(--transition);
      }
      
      .sidebar.collapsed + .sidebar-toggle {
        left: 20px;
      }
    }

    /* Scroll personalizado para la sidebar */
    .sidebar::-webkit-scrollbar {
      width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 4px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(255, 255, 255, 0.5);
    }
  </style>
</head>
<body>

<!-- Botón toggle para la sidebar -->
<button class="sidebar-toggle" id="sidebarToggle">
  <i class="bi bi-list" style="font-size: 1.2rem;"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <!-- Header del usuario -->
  <div class="sidebar-header">
    <div class="user-avatar">
      <?= strtoupper(substr($nombre, 0, 1)) ?>
    </div>
    <h4 class="user-name"><?= $nombre ?></h4>
    <p class="user-role">Administrador</p>
  </div>

  <!-- Navegación principal -->
  <div class="sidebar-nav">
    <div class="nav-section">
      <div class="nav-section-title">Principal</div>
      
      <div class="nav-item">
        <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
          <i class="bi bi-house-door nav-icon"></i>
          <span class="nav-text">Panel Principal</span>
        </a>
      </div>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Gestión</div>
      
      <div class="nav-item">
        <a href="productos.php" class="nav-link <?= $current_page === 'productos.php' ? 'active' : '' ?>">
          <i class="bi bi-box-seam nav-icon"></i>
          <span class="nav-text">Gestión de Productos</span>
        </a>
      </div>

      <div class="nav-item">
        <a href="ventas.php" class="nav-link <?= $current_page === 'ventas.php' ? 'active' : '' ?>">
          <i class="bi bi-receipt nav-icon"></i>
          <span class="nav-text">Registro de Ventas</span>
        </a>
      </div>

      <div class="nav-item">
        <a href="vendedores.php" class="nav-link <?= $current_page === 'vendedores.php' ? 'active' : '' ?>">
          <i class="bi bi-people nav-icon"></i>
          <span class="nav-text">Gestión de Vendedores</span>
        </a>
      </div>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Reportes</div>
      
      <div class="nav-item">
        <a href="informes.php" class="nav-link <?= $current_page === 'informes.php' ? 'active' : '' ?>">
          <i class="bi bi-graph-up nav-icon"></i>
          <span class="nav-text">Informes por Día</span>
        </a>
      </div>

      <div class="nav-item">
        <a href="apertura_cierre.php" class="nav-link <?= $current_page === 'apertura_cierre.php' ? 'active' : '' ?>">
          <i class="bi bi-calendar-check nav-icon"></i>
          <span class="nav-text">Cierre del Día</span>
        </a>
      </div>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Sistema</div>
      
      <div class="nav-item">
        <a href="seguridad.php" class="nav-link <?= $current_page === 'seguridad.php' ? 'active' : '' ?>">
          <i class="bi bi-shield-check nav-icon"></i>
          <span class="nav-text">Seguridad</span>
        </a>
      </div>

      <div class="nav-item">
        <a href="perfil.php" class="nav-link <?= $current_page === 'perfil.php' ? 'active' : '' ?>">
          <i class="bi bi-person-circle nav-icon"></i>
          <span class="nav-text">Perfil del Administrador</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Sección de logout -->
  <div class="logout-section">
    <div class="nav-item">
      <a href="../logout.php" class="nav-link logout-link">
        <i class="bi bi-box-arrow-right nav-icon"></i>
        <span class="nav-text">Cerrar Sesión</span>
      </a>
    </div>
  </div>
</div>

<!-- Inicio del contenido -->
<div class="main-content" id="mainContent">
  <div class="content-wrapper">

<script>
document.addEventListener('DOMContentLoaded', function() {
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
            sidebarVisible = true;
        } else if (newWidth <= 768 && sidebarVisible) {
            // Cambio de desktop a mobile
            sidebar.classList.remove('collapsed', 'show');
            mainContent.classList.remove('expanded');
            sidebarVisible = false;
        }
    });

    // Mostrar/ocultar botón toggle según el tamaño de pantalla
    function updateToggleVisibility() {
        if (window.innerWidth <= 768) {
            sidebarToggle.classList.add('visible');
        } else {
            sidebarToggle.classList.add('visible'); // Siempre visible para permitir colapsar
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
