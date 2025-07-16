/**
 * Sistema de Transiciones de Página - Sistema Bazar
 * Efectos únicos para cada tipo de usuario (vendedor/admin)
 */

class PageTransitions {
    constructor() {
        this.init();
    }

    init() {
        this.createTransitionOverlay();
        this.attachFormHandlers();
        this.setupPageEntry();
    }

    // Crear overlay para transiciones
    createTransitionOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'page-transition-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f0f 100%);
        `;
        document.body.appendChild(overlay);
    }

    // Efecto para vendedor - Ondas celestiales
    vendorTransition() {
        return new Promise((resolve) => {
            const overlay = document.getElementById('page-transition-overlay');
            overlay.innerHTML = `
                <div class="vendor-transition-container" style="
                    position: relative;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                ">
                    <div class="wave wave1" style="
                        position: absolute;
                        width: 120%;
                        height: 120%;
                        background: linear-gradient(45deg, #667eea, #764ba2);
                        border-radius: 50%;
                        transform: translate(-50%, -50%) scale(0);
                        top: 50%;
                        left: 50%;
                        animation: vendorWave 1.5s ease-out forwards;
                    "></div>
                    <div class="wave wave2" style="
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(45deg, #feca57, #ff6b6b);
                        border-radius: 50%;
                        transform: translate(-50%, -50%) scale(0);
                        top: 50%;
                        left: 50%;
                        animation: vendorWave 1.5s ease-out 0.2s forwards;
                    "></div>
                    <div class="vendor-particles" style="
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        top: 0;
                        left: 0;
                    "></div>
                    <div class="vendor-text" style="
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        color: white;
                        font-size: 2rem;
                        font-weight: 300;
                        text-align: center;
                        opacity: 0;
                        animation: fadeInText 1s ease-out 0.5s forwards;
                    ">
                        Bienvenido Vendedor
                        <div style="font-size: 0.8rem; margin-top: 10px; opacity: 0.8;">
                            Cargando tu panel...
                        </div>
                    </div>
                </div>
            `;

            // Crear partículas para vendedor
            this.createVendorParticles();

            overlay.style.opacity = '1';
            overlay.style.pointerEvents = 'all';

            setTimeout(() => {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.innerHTML = '';
                    overlay.style.pointerEvents = 'none';
                    resolve();
                }, 500);
            }, 2500);
        });
    }

    // Efecto para administrador - Portal dimensional
    adminTransition() {
        return new Promise((resolve) => {
            const overlay = document.getElementById('page-transition-overlay');
            overlay.innerHTML = `
                <div class="admin-transition-container" style="
                    position: relative;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                    background: linear-gradient(135deg, #0c0c0c 0%, #2d1b69 50%, #0c0c0c 100%);
                ">
                    <div class="portal-ring ring1" style="
                        position: absolute;
                        width: 200px;
                        height: 200px;
                        border: 3px solid #ff6b6b;
                        border-radius: 50%;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%) scale(0);
                        animation: portalExpand 2s ease-out forwards;
                        box-shadow: 0 0 50px #ff6b6b;
                    "></div>
                    <div class="portal-ring ring2" style="
                        position: absolute;
                        width: 150px;
                        height: 150px;
                        border: 2px solid #feca57;
                        border-radius: 50%;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%) scale(0);
                        animation: portalExpand 2s ease-out 0.2s forwards;
                        box-shadow: 0 0 30px #feca57;
                    "></div>
                    <div class="portal-ring ring3" style="
                        position: absolute;
                        width: 100px;
                        height: 100px;
                        border: 2px solid #48dbfb;
                        border-radius: 50%;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%) scale(0);
                        animation: portalExpand 2s ease-out 0.4s forwards;
                        box-shadow: 0 0 20px #48dbfb;
                    "></div>
                    <div class="admin-grid" style="
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        background-image: 
                            linear-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                            linear-gradient(90deg, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
                        background-size: 50px 50px;
                        animation: gridMove 3s linear infinite;
                        opacity: 0.3;
                    "></div>
                    <div class="admin-text" style="
                        position: absolute;
                        top: 60%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        color: white;
                        font-size: 2rem;
                        font-weight: 300;
                        text-align: center;
                        opacity: 0;
                        animation: fadeInText 1s ease-out 0.8s forwards;
                    ">
                        Panel de Administración
                        <div style="font-size: 0.8rem; margin-top: 10px; opacity: 0.8;">
                            Acceso autorizado...
                        </div>
                    </div>
                </div>
            `;

            // Crear partículas para admin
            this.createAdminParticles();

            overlay.style.opacity = '1';
            overlay.style.pointerEvents = 'all';

            setTimeout(() => {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.innerHTML = '';
                    overlay.style.pointerEvents = 'none';
                    resolve();
                }, 500);
            }, 3000);
        });
    }

    // Crear partículas para efecto vendedor
    createVendorParticles() {
        const container = document.querySelector('.vendor-particles');
        if (!container) return;

        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: absolute;
                width: 4px;
                height: 4px;
                background: rgba(255, 255, 255, 0.8);
                border-radius: 50%;
                top: ${Math.random() * 100}%;
                left: ${Math.random() * 100}%;
                animation: vendorParticleFloat ${3 + Math.random() * 2}s ease-in-out infinite;
                animation-delay: ${Math.random() * 2}s;
            `;
            container.appendChild(particle);
        }
    }

    // Crear partículas para efecto admin
    createAdminParticles() {
        const container = document.querySelector('.admin-transition-container');
        if (!container) return;

        for (let i = 0; i < 15; i++) {
            const particle = document.createElement('div');
            particle.style.cssText = `
                position: absolute;
                width: 2px;
                height: 20px;
                background: linear-gradient(to bottom, rgba(255, 107, 107, 0.8), transparent);
                top: ${Math.random() * 100}%;
                left: ${Math.random() * 100}%;
                animation: adminParticleFloat ${2 + Math.random() * 2}s linear infinite;
                animation-delay: ${Math.random() * 2}s;
            `;
            container.appendChild(particle);
        }
    }

    // Configurar entrada de página con animación
    setupPageEntry() {
        // Detectar si venimos de login
        const urlParams = new URLSearchParams(window.location.search);
        const fromLogin = urlParams.get('from') === 'login';
        
        if (fromLogin) {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 1s ease-in-out';
                document.body.style.opacity = '1';
            }, 100);
        }

        // Animación de entrada para elementos
        this.animatePageElements();
    }

    // Animar elementos de la página al cargar
    animatePageElements() {
        const cards = document.querySelectorAll('.card');
        const titles = document.querySelectorAll('h1, h2, h3');
        
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 200 + (index * 100));
        });

        titles.forEach((title, index) => {
            title.style.opacity = '0';
            title.style.transform = 'translateX(-30px)';
            setTimeout(() => {
                title.style.transition = 'all 0.8s ease-out';
                title.style.opacity = '1';
                title.style.transform = 'translateX(0)';
            }, 100 + (index * 150));
        });
    }

    // Adjuntar manejadores a formularios de login
    attachFormHandlers() {
        const loginForm = document.querySelector('form[action="auth.php"]');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                // No prevenir el envío, solo agregar efectos visuales
                this.showLoginLoading();
            });
        }
    }

    // Mostrar efecto de carga durante login
    showLoginLoading() {
        const overlay = document.getElementById('page-transition-overlay');
        overlay.innerHTML = `
            <div style="
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                color: white;
            ">
                <div style="
                    width: 40px;
                    height: 40px;
                    border: 3px solid rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    border-top-color: white;
                    animation: spin 1s ease-in-out infinite;
                    margin: 0 auto 20px;
                "></div>
                <div style="font-size: 1.1rem; opacity: 0.9;">
                    Verificando credenciales...
                </div>
            </div>
        `;
        overlay.style.opacity = '1';
        overlay.style.pointerEvents = 'all';
    }

    // Agregar estilos CSS para animaciones
    injectStyles() {
        const styles = `
            <style>
                @keyframes vendorWave {
                    0% { transform: translate(-50%, -50%) scale(0); opacity: 0.8; }
                    70% { opacity: 0.6; }
                    100% { transform: translate(-50%, -50%) scale(3); opacity: 0; }
                }

                @keyframes vendorParticleFloat {
                    0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.3; }
                    50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
                }

                @keyframes portalExpand {
                    0% { transform: translate(-50%, -50%) scale(0) rotate(0deg); opacity: 0; }
                    50% { opacity: 1; }
                    100% { transform: translate(-50%, -50%) scale(2) rotate(360deg); opacity: 0; }
                }

                @keyframes adminParticleFloat {
                    0% { transform: translateY(100vh) translateX(0px); opacity: 0; }
                    20% { opacity: 1; }
                    80% { opacity: 1; }
                    100% { transform: translateY(-100px) translateX(50px); opacity: 0; }
                }

                @keyframes gridMove {
                    0% { transform: translate(0, 0); }
                    100% { transform: translate(50px, 50px); }
                }

                @keyframes fadeInText {
                    0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
                    100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
                }

                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                /* Efectos hover mejorados para cards */
                .card {
                    transition: all 0.3s ease;
                }

                .card:hover {
                    transform: translateY(-8px);
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
                }

                /* Animaciones de enlace */
                .nav-link {
                    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                }

                .nav-link:hover {
                    transform: translateX(8px);
                }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', styles);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const transitions = new PageTransitions();
    transitions.injectStyles();
});

// Exportar para uso global
window.PageTransitions = PageTransitions;
