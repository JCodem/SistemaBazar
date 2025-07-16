<?php
require_once '../../includes/auth_middleware.php';
require_once '../../includes/rol_middleware_vendedor.php';
$titulo = 'Dashboard del Vendedor';
include '../../includes/layout_vendedor.php';
?>

<style>
/* Estilo minimalista matching con login */
.dashboard-container {
    background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f0f 100%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.dashboard-stars {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
}

.dashboard-star {
    position: absolute;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    animation: dashboardTwinkle 4s ease-in-out infinite;
}

@keyframes dashboardTwinkle {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.2); }
}

.content-overlay {
    position: relative;
    z-index: 10;
    background: rgba(15, 15, 25, 0.85);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    margin: 2rem;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.dashboard-title {
    color: #ffffff;
    font-weight: 300;
    font-size: 2.5rem;
    text-align: center;
    margin-bottom: 1rem;
    letter-spacing: 1px;
}

.dashboard-subtitle {
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
    margin-bottom: 3rem;
    font-size: 1.1rem;
}

.dashboard-card {
    background: rgba(25, 25, 35, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 2rem;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-align: center;
    backdrop-filter: blur(5px);
}

.dashboard-card:hover {
    transform: translateY(-8px);
    border-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    background: rgba(35, 35, 45, 0.9);
    text-decoration: none;
    color: inherit;
}

.card-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.card-title {
    color: #ffffff;
    font-weight: 500;
    font-size: 1.3rem;
    margin-bottom: 0.8rem;
    letter-spacing: 0.5px;
}

.card-description {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    line-height: 1.5;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: rgba(25, 25, 35, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: 600;
    color: #ffffff;
    display: block;
}

.stat-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-title {
        font-size: 2rem;
    }
    
    .content-overlay {
        margin: 1rem;
        padding: 1.5rem;
    }
    
    .card-icon {
        font-size: 2.5rem;
    }
}
</style>

<div class="dashboard-container">
    <!-- Estrellas de fondo -->
    <div class="dashboard-stars" id="dashboardStars"></div>
    
    <div class="content-overlay">
        <h1 class="dashboard-title">Panel del Vendedor</h1>
        <p class="dashboard-subtitle">Gestiona tus ventas y consulta tu información de manera eficiente</p>

        <!-- Estadísticas rápidas -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">0</span>
                <div class="stat-label">Ventas Hoy</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">$0</span>
                <div class="stat-label">Ingresos Hoy</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">0</span>
                <div class="stat-label">Productos Vendidos</div>
            </div>
        </div>

        <!-- Grid de funcionalidades -->
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <a href="pos.php" class="dashboard-card">
                    <div class="card-icon">🛒</div>
                    <h5 class="card-title">Punto de Venta</h5>
                    <p class="card-description">Procesa ventas de manera rápida y eficiente</p>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <a href="inventario.php" class="dashboard-card">
                    <div class="card-icon">📦</div>
                    <h5 class="card-title">Inventario</h5>
                    <p class="card-description">Consulta el stock y gestiona productos</p>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <a href="historial_ventas.php" class="dashboard-card">
                    <div class="card-icon">📊</div>
                    <h5 class="card-title">Historial de Ventas</h5>
                    <p class="card-description">Consulta tus ventas anteriores con detalle</p>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <a href="perfil.php" class="dashboard-card">
                    <div class="card-icon">👤</div>
                    <h5 class="card-title">Mi Perfil</h5>
                    <p class="card-description">Edita tus datos personales y configuración</p>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <a href="descargar_reporte.php" class="dashboard-card">
                    <div class="card-icon">📥</div>
                    <h5 class="card-title">Reporte Diario</h5>
                    <p class="card-description">Descarga un resumen de tus ventas del día</p>
                </a>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <a href="../logout.php" class="dashboard-card" style="border-color: rgba(255, 107, 107, 0.3);">
                    <div class="card-icon" style="background: linear-gradient(135deg, #ff6b6b, #feca57); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">🚪</div>
                    <h5 class="card-title">Cerrar Sesión</h5>
                    <p class="card-description">Terminar tu sesión de trabajo segura</p>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/page-transitions.js"></script>
<script>
// Crear estrellas para el dashboard
function createDashboardStars() {
    const container = document.getElementById('dashboardStars');
    const starCount = 25;
    
    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.className = 'dashboard-star';
        star.style.left = Math.random() * 100 + '%';
        star.style.top = Math.random() * 100 + '%';
        star.style.width = (Math.random() * 3 + 1) + 'px';
        star.style.height = (Math.random() * 3 + 1) + 'px';
        star.style.animationDelay = Math.random() * 4 + 's';
        star.style.animationDuration = (Math.random() * 3 + 3) + 's';
        
        if (Math.random() > 0.7) {
            star.style.filter = 'blur(1px)';
        }
        
        container.appendChild(star);
    }
}

// Animación de entrada para las tarjetas
function animateDashboardCards() {
    const cards = document.querySelectorAll('.dashboard-card');
    const statCards = document.querySelectorAll('.stat-card');
    
    // Animar estadísticas primero
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease-out';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + (index * 100));
    });
    
    // Luego animar tarjetas principales
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(40px) scale(0.9)';
        setTimeout(() => {
            card.style.transition = 'all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
        }, 400 + (index * 150));
    });
}

document.addEventListener('DOMContentLoaded', function() {
    createDashboardStars();
    animateDashboardCards();
    
    // Auto-trigger transition if coming from login
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from') === 'login') {
        setTimeout(() => {
            if (typeof PageTransitions !== 'undefined') {
                const pageTransitions = new PageTransitions();
                pageTransitions.vendorTransition();
            }
        }, 100);
    }
});
</script>

<?php include '../../includes/footer_vendedor.php'; ?>
