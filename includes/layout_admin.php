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
  
  <!-- Script de mantenimiento de posición de scroll -->
  <script src="assets/js/scroll-position.js" defer></script>
  <style>
    :root {
      --sidebar-width: 280px;
      
      /* Dark theme (default) */
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --bg-tertiary: #334155;
      --bg-card: #1e293b;
      --bg-hover: #334155;
      --bg-active: #475569;
      
      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --text-muted: #64748b;
      
      --border-color: #334155;
      --border-light: #475569;
      
      --accent-primary: #3b82f6;
      --accent-secondary: #8b5cf6;
      --accent-success: #10b981;
      --accent-warning: #f59e0b;
      --accent-danger: #ef4444;
      
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      
      --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    [data-theme="light"] {
      /* Light theme */
      --bg-primary: #ffffff;
      --bg-secondary: #f8fafc;
      --bg-tertiary: #e2e8f0;
      --bg-card: #ffffff;
      --bg-hover: #f1f5f9;
      --bg-active: #e2e8f0;
      
      --text-primary: #0f172a;
      --text-secondary: #334155;
      --text-muted: #64748b;
      
      --border-color: #e2e8f0;
      --border-light: #cbd5e1;
      
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
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
      background-color: var(--bg-primary);
      color: var(--text-primary);
      transition: var(--transition);
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      margin: 0 !important;
      padding: 0 !important;
      box-sizing: border-box !important;
      overflow-x: hidden !important;
      display: flex !important;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
      min-height: 100vh !important;
    }

    .sidebar {
      width: var(--sidebar-width);
      background: var(--bg-secondary);
      border-right: 1px solid var(--border-color);
      flex-shrink: 0;
      padding: 0;
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      z-index: 1000;
      transition: var(--transition-slow);
      transform: translateX(0);
    }

    .sidebar.collapsed {
      transform: translateX(-100%);
    }

    .sidebar-header {
      padding: 2rem 1.5rem 1.5rem;
      border-bottom: 1px solid var(--border-color);
      position: relative;
    }

    .user-avatar {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
      border-radius: 16px;
      margin: 0 auto 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      font-weight: 600;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .user-name {
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0;
      color: var(--text-primary);
      text-align: center;
    }

    .user-role {
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
      text-align: center;
    }

    .theme-toggle {
      position: absolute;
      top: 1rem;
      right: 1rem;
      width: 40px;
      height: 40px;
      border: 1px solid var(--border-color);
      background: var(--bg-card);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: var(--transition);
      color: var(--text-secondary);
    }

    .theme-toggle:hover {
      background: var(--bg-hover);
      color: var(--accent-primary);
    }

    .sidebar-nav {
      flex: 1;
      padding: 1rem 0;
      overflow-y: auto;
    }

    .nav-section {
      margin-bottom: 2rem;
    }

    .nav-section-title {
      padding: 0 1.5rem 0.75rem;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
    }

    .nav-item {
      margin: 0.25rem 1rem;
      border-radius: 8px;
      overflow: hidden;
    }

    .nav-link {
      color: var(--text-secondary);
      padding: 0.75rem 1rem;
      display: flex;
      align-items: center;
      text-decoration: none;
      transition: var(--transition);
      position: relative;
      border-radius: 8px;
      font-weight: 500;
      font-size: 0.875rem;
    }

    .nav-link:hover {
      background: var(--bg-hover);
      color: var(--text-primary);
    }

    .nav-link.active {
      background: var(--accent-primary);
      color: white;
    }

    .nav-icon {
      font-size: 1.125rem;
      margin-right: 0.75rem;
      width: 20px;
      text-align: center;
      transition: var(--transition);
    }

    .nav-text {
      font-size: 0.875rem;
      font-weight: 500;
    }

    .logout-section {
      flex-shrink: 0;
      padding: 1rem;
      border-top: 1px solid var(--border-color);
      margin-top: auto;
    }

    .logout-link {
      color: var(--accent-danger) !important;
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .logout-link:hover {
      background: rgba(239, 68, 68, 0.2) !important;
      color: var(--accent-danger) !important;
    }

    .main-content {
      flex-grow: 1 !important;
      background: var(--bg-primary) !important;
      min-height: 100vh !important;
      position: relative !important;
      margin-left: var(--sidebar-width) !important;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      width: calc(100% - var(--sidebar-width)) !important;
    }

    .main-content.expanded {
      margin-left: 0 !important;
      width: 100% !important;
    }

    .sidebar-toggle {
      position: fixed !important;
      top: 20px !important;
      left: calc(var(--sidebar-width) + 20px) !important;
      z-index: 1001 !important;
      background: var(--bg-card) !important;
      border: 1px solid var(--border-color) !important;
      border-radius: 8px !important;
      color: var(--text-primary) !important;
      padding: 10px !important;
      cursor: pointer !important;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      display: block !important;
      box-shadow: var(--shadow) !important;
    }

    .sidebar-toggle.collapsed {
      left: 20px !important;
    }

    .sidebar-toggle:hover {
      background: var(--bg-hover) !important;
      box-shadow: var(--shadow-md) !important;
      transform: scale(1.05) !important;
    }

    .content-wrapper {
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
      min-height: calc(100vh - 4rem);
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
      body .main-content {
        margin-left: var(--sidebar-width) !important;
        width: calc(100% - var(--sidebar-width)) !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
      }
      
      .sidebar.collapsed {
        transform: translateX(-100%) !important;
      }
      
      body .main-content.expanded {
        margin-left: 0 !important;
        width: 100% !important;
      }
    }

    /* Scroll personalizado */
    .sidebar::-webkit-scrollbar {
      width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
      background: var(--bg-secondary);
    }

    .sidebar::-webkit-scrollbar-thumb {
      background: var(--border-light);
      border-radius: 4px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
      background: var(--text-muted);
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
    <div class="theme-toggle" id="themeToggle" title="Cambiar tema">
      <i class="bi bi-moon-fill" id="themeIcon"></i>
    </div>
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
          <span class="nav-text">Gestión de Usuarios</span>
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
          <span class="nav-text">Control de Caja</span>
        </a>
      </div>

      <div class="nav-item">
        <a href="historial_sesiones.php" class="nav-link <?= $current_page === 'historial_sesiones.php' ? 'active' : '' ?>">
          <i class="bi bi-clock-history nav-icon"></i>
          <span class="nav-text">Historial de Sesiones</span>
        </a>
      </div>
    </div>

    <div class="nav-section">
      <div class="nav-section-title">Sistema</div>
      
      <div class="nav-item">
        <a href="seguridad.php" class="nav-link <?= $current_page === 'seguridad.php' ? 'active' : '' ?>">
          <i class="bi bi-shield-check nav-icon"></i>
          <span class="nav-text">Seguridad y Configuración</span>
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
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    let sidebarVisible = window.innerWidth > 768;

    // Inicializar posición del botón toggle
    if (window.innerWidth <= 768) {
        sidebarToggle.classList.add('collapsed');
    }

    // Theme management
    const currentTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeIcon(currentTheme);

    function updateThemeIcon(theme) {
        if (theme === 'light') {
            themeIcon.className = 'bi bi-sun-fill';
        } else {
            themeIcon.className = 'bi bi-moon-fill';
        }
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
        
        // Add smooth transition effect
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    // Toggle sidebar
    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            sidebarToggle.classList.toggle('collapsed');
            
            // Debug: verificar que las clases se están aplicando
            console.log('Sidebar collapsed:', sidebar.classList.contains('collapsed'));
            console.log('MainContent expanded:', mainContent.classList.contains('expanded'));
            
            const icon = sidebarToggle.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'bi bi-arrow-right';
            } else {
                icon.className = 'bi bi-list';
            }
        }
    }

    // Event listeners
    sidebarToggle.addEventListener('click', toggleSidebar);
    themeToggle.addEventListener('click', toggleTheme);

    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        const newWidth = window.innerWidth;
        
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

    // Smooth hover effects for nav links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(4px)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(0)';
            }
        });
    });
});
</script>
