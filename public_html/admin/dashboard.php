<?php require_once '../../includes/layout_admin.php'; ?>

<style>
/* Estilo para administrador - Portal dimensional */
.admin-dashboard {
    background: linear-gradient(135deg, #0c0c0c 0%, #2d1b69 50%, #0c0c0c 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    padding: 0;
    margin: 0;
}

.admin-grid-bg {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
    background-size: 60px 60px;
    animation: adminGridMove 20s linear infinite;
    z-index: 1;
}

@keyframes adminGridMove {
    0% { transform: translate(0, 0); }
    100% { transform: translate(60px, 60px); }
}

.admin-particles {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    pointer-events: none;
}

.admin-particle {
    position: absolute;
    width: 2px;
    height: 20px;
    background: linear-gradient(to bottom, rgba(255, 107, 107, 0.8), transparent);
    animation: adminParticleMove 6s linear infinite;
}

@keyframes adminParticleMove {
    0% { transform: translateY(100vh) translateX(0px); opacity: 0; }
    20% { opacity: 1; }
    80% { opacity: 1; }
    100% { transform: translateY(-100px) translateX(50px); opacity: 0; }
}

.admin-content {
    position: relative;
    z-index: 10;
    background: rgba(15, 15, 25, 0.9);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    margin: 2rem;
    padding: 3rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.admin-header {
    text-align: center;
    margin-bottom: 3rem;
}

.admin-title {
    font-size: 3rem;
    font-weight: 300;
    color: #ffffff;
    margin-bottom: 1rem;
    letter-spacing: 2px;
    background: linear-gradient(135deg, #ff6b6b, #feca57, #48dbfb);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.admin-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.admin-welcome {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.admin-stat-card {
    background: rgba(30, 30, 40, 0.8);
    border: 2px solid transparent;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.admin-stat-card::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff6b6b);
    border-radius: 15px;
    z-index: -1;
    animation: adminBorderRotate 4s linear infinite;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.admin-stat-card:hover::before {
    opacity: 1;
}

@keyframes adminBorderRotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.admin-stat-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

.admin-stat-icon {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #ff6b6b, #feca57);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.admin-stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #ffffff;
    display: block;
    margin-bottom: 0.5rem;
}

.admin-stat-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1rem;
    font-weight: 500;
}

.admin-quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.admin-action-card {
    background: rgba(25, 25, 35, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 1.5rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.admin-action-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.6s ease;
}

.admin-action-card:hover::after {
    left: 100%;
}

.admin-action-card:hover {
    transform: translateY(-8px) scale(1.02);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    text-decoration: none;
    color: inherit;
}

.admin-action-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #48dbfb;
}

.admin-action-title {
    color: #ffffff;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
}

.admin-action-desc {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-title {
        font-size: 2.2rem;
    }
    
    .admin-content {
        margin: 1rem;
        padding: 2rem;
    }
    
    .admin-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="admin-dashboard">
    <!-- Grid de fondo -->
    <div class="admin-grid-bg"></div>
    
    <!-- Partículas -->
    <div class="admin-particles" id="adminParticles"></div>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1 class="admin-title">Centro de Control</h1>
            <p class="admin-subtitle">Panel de Administración</p>
            <p class="admin-welcome">¡Bienvenido, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?>!</p>
        </div>

        <!-- Estadísticas principales -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-icon">💰</div>
                <span class="admin-stat-number">$0.00</span>
                <div class="admin-stat-label">Total Ventas Hoy</div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">👥</div>
                <span class="admin-stat-number">0</span>
                <div class="admin-stat-label">Vendedores Activos</div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">📦</div>
                <span class="admin-stat-number">0</span>
                <div class="admin-stat-label">Productos en Stock</div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">📊</div>
                <span class="admin-stat-number">0</span>
                <div class="admin-stat-label">Transacciones Hoy</div>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="admin-quick-actions">
            <a href="productos.php" class="admin-action-card">
                <div class="admin-action-icon">📦</div>
                <div class="admin-action-title">Gestión de Productos</div>
                <div class="admin-action-desc">Administrar inventario y catálogo</div>
            </a>
            
            <a href="ventas.php" class="admin-action-card">
                <div class="admin-action-icon">🧾</div>
                <div class="admin-action-title">Registro de Ventas</div>
                <div class="admin-action-desc">Consultar todas las transacciones</div>
            </a>
            
            <a href="informes.php" class="admin-action-card">
                <div class="admin-action-icon">📈</div>
                <div class="admin-action-title">Informes por Día</div>
                <div class="admin-action-desc">Análisis y reportes detallados</div>
            </a>
            
            <a href="vendedores.php" class="admin-action-card">
                <div class="admin-action-icon">👥</div>
                <div class="admin-action-title">Gestión de Vendedores</div>
                <div class="admin-action-desc">Administrar personal de ventas</div>
            </a>
            
            <a href="apertura_cierre.php" class="admin-action-card">
                <div class="admin-action-icon">📅</div>
                <div class="admin-action-title">Cierre del Día</div>
                <div class="admin-action-desc">Operaciones de apertura y cierre</div>
            </a>
            
            <a href="seguridad.php" class="admin-action-card">
                <div class="admin-action-icon">🛡️</div>
                <div class="admin-action-title">Seguridad</div>
                <div class="admin-action-desc">Configuración y auditoría</div>
            </a>
        </div>
    </div>
</div>

<script src="../assets/js/page-transitions.js"></script>
<script>
// Crear partículas para administrador
function createAdminParticles() {
    const container = document.getElementById('adminParticles');
    const particleCount = 15;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'admin-particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 5) + 's';
        
        // Variar el color
        const colors = [
            'linear-gradient(to bottom, rgba(255, 107, 107, 0.8), transparent)',
            'linear-gradient(to bottom, rgba(254, 202, 87, 0.8), transparent)',
            'linear-gradient(to bottom, rgba(72, 219, 251, 0.8), transparent)'
        ];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];
        
        container.appendChild(particle);
    }
}

// Animación de entrada para elementos
function animateAdminElements() {
    const statCards = document.querySelectorAll('.admin-stat-card');
    const actionCards = document.querySelectorAll('.admin-action-card');
    
    // Animar estadísticas
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px) scale(0.8)';
        setTimeout(() => {
            card.style.transition = 'all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
        }, 200 + (index * 150));
    });
    
    // Animar acciones rápidas
    actionCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(40px) rotateX(15deg)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.25, 0.8, 0.25, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) rotateX(0deg)';
        }, 600 + (index * 100));
    });
}

document.addEventListener('DOMContentLoaded', function() {
    createAdminParticles();
    animateAdminElements();
    
    // Auto-trigger transition if coming from login
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from') === 'login') {
        setTimeout(() => {
            if (typeof PageTransitions !== 'undefined') {
                const pageTransitions = new PageTransitions();
                pageTransitions.adminTransition();
            }
        }, 100);
    }
});
</script>


