<?php
// Función para escribir logs
function escribir_log($mensaje) {
    $fecha = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $linea = "[$fecha] [$ip] $mensaje\n";
    file_put_contents(__DIR__ . '/../includes/log.txt', $linea, FILE_APPEND);
}
session_start();
require_once '../includes/funciones.php'; // CSRF helpers
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - Sistema Bazar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            --accent-gradient: linear-gradient(135deg, #3a3a3a 0%, #1a1a1a 100%);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #a0a0a0;
            --background-dark: #0f0f0f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            overflow: hidden;
            background: linear-gradient(135deg, #0a0a0f 0%, #1a1a2e 50%, #0f0f0f 100%);
            position: relative;
        }

        /* Fondo de estrellas minimalista */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .stars {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .star {
            position: absolute;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: twinkle 4s ease-in-out infinite;
            filter: blur(0.5px);
        }

        .star:nth-child(1) { width: 2px; height: 2px; top: 20%; left: 10%; animation-delay: 0s; }
        .star:nth-child(2) { width: 1px; height: 1px; top: 30%; left: 20%; animation-delay: 1s; }
        .star:nth-child(3) { width: 3px; height: 3px; top: 40%; left: 30%; animation-delay: 2s; filter: blur(1px); }
        .star:nth-child(4) { width: 1px; height: 1px; top: 10%; left: 40%; animation-delay: 3s; }
        .star:nth-child(5) { width: 2px; height: 2px; top: 60%; left: 50%; animation-delay: 0.5s; }
        .star:nth-child(6) { width: 1px; height: 1px; top: 70%; left: 60%; animation-delay: 1.5s; }
        .star:nth-child(7) { width: 2px; height: 2px; top: 15%; left: 70%; animation-delay: 2.5s; }
        .star:nth-child(8) { width: 3px; height: 3px; top: 25%; left: 80%; animation-delay: 3.5s; filter: blur(1px); }
        .star:nth-child(9) { width: 1px; height: 1px; top: 50%; left: 90%; animation-delay: 4s; }
        .star:nth-child(10) { width: 2px; height: 2px; top: 80%; left: 15%; animation-delay: 0.8s; }
        .star:nth-child(11) { width: 1px; height: 1px; top: 35%; left: 25%; animation-delay: 1.8s; }
        .star:nth-child(12) { width: 2px; height: 2px; top: 45%; left: 35%; animation-delay: 2.8s; }
        .star:nth-child(13) { width: 1px; height: 1px; top: 55%; left: 45%; animation-delay: 3.8s; }
        .star:nth-child(14) { width: 3px; height: 3px; top: 65%; left: 55%; animation-delay: 4.8s; filter: blur(1px); }
        .star:nth-child(15) { width: 1px; height: 1px; top: 75%; left: 65%; animation-delay: 0.3s; }
        .star:nth-child(16) { width: 2px; height: 2px; top: 85%; left: 75%; animation-delay: 1.3s; }
        .star:nth-child(17) { width: 1px; height: 1px; top: 5%; left: 85%; animation-delay: 2.3s; }
        .star:nth-child(18) { width: 2px; height: 2px; top: 90%; left: 5%; animation-delay: 3.3s; }

        @keyframes twinkle {
            0%, 100% {
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        /* Estrellas difuminadas de fondo */
        .background-glow {
            position: absolute;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                radial-gradient(circle at 70% 60%, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                radial-gradient(circle at 90% 20%, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                radial-gradient(circle at 10% 70%, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            animation: gentle-glow 8s ease-in-out infinite;
        }

        @keyframes gentle-glow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 0.8; }
        }

        /* Contenedor principal */
        .login-container {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        /* Tarjeta de login minimalista */
        .login-card {
            background: rgba(15, 15, 25, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            padding: 2.5rem;
            width: 100%;
            max-width: 380px;
            position: relative;
        }

        .login-card h3 {
            color: var(--text-primary);
            font-weight: 300;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 1px;
        }

        .form-control {
            background: rgba(25, 25, 35, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: #fff !important;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .form-control:focus {
            background: rgba(30, 30, 40, 0.9);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 400;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: rgba(25, 25, 35, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            padding: 0.75rem;
            font-weight: 400;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: var(--text-primary);
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: rgba(35, 35, 45, 0.9);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .register-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 400;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .register-link:hover {
            color: var(--text-primary);
            text-decoration: none;
        }

        .alert {
            border-radius: 8px;
            border: none;
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            color: #93c5fd;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-card {
                padding: 2rem;
                margin: 1rem;
            }
            
            .shape:nth-child(n+4) {
                display: none;
            }
            
            .grid-background {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Fondo minimalista con estrellas -->
<div class="animated-background">
    <div class="background-glow"></div>
    <div class="stars">
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
        <div class="star"></div>
    </div>
</div>

<!-- Contenedor principal -->
<div class="login-container">
    <div class="login-card">
        <h3 class="text-center">Iniciar Sesión 1</h3>

    <?php if (isset($_SESSION['error'])): ?>
        <?php escribir_log("LOGIN UI ERROR: " . $_SESSION['error']); ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
        <?php escribir_log("LOGIN UI MENSAJE: " . $_SESSION['mensaje']); ?>
        <div class="alert alert-info"><?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['debug_info']) && isset($_GET['debug'])): ?>
        <div class="alert alert-warning">
            <h5>Información de depuración:</h5>
            <pre><?php print_r($_SESSION['debug_info']); unset($_SESSION['debug_info']); ?></pre>
        </div>
    <?php endif; ?>

    <form action="auth.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <div class="mb-3">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input type="email" name="correo" class="form-control" required placeholder="usuario@ejemplo.com">
        </div>

        <div class="mb-3">
            <label for="contrasena" class="form-label">Contraseña</label>
            <input type="password" name="contrasena" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Ingresar</button>
    </form>

        <div class="text-center mt-3">
            <a href="register.php" class="register-link">¿No tienes cuenta? Regístrate</a>
        </div>
    </div>
</div>

<script>
    // Crear estrellas dinámicas adicionales
    function createDynamicStars() {
        const starsContainer = document.querySelector('.stars');
        const starCount = 30;
        
        for (let i = 0; i < starCount; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 8 + 's';
            star.style.animationDuration = (Math.random() * 3 + 3) + 's';
            
            // Variaciones de tamaño y brillo
            const size = Math.random() * 3 + 1;
            star.style.width = size + 'px';
            star.style.height = size + 'px';
            
            // Algunas estrellas más difuminadas
            if (Math.random() > 0.7) {
                star.style.filter = 'blur(1px)';
                star.style.opacity = '0.6';
            }
            
            starsContainer.appendChild(star);
        }
    }

    // Efecto sutil de cursor
    function createSubtleCursorEffect() {
        document.addEventListener('mousemove', function(e) {
            if (Math.random() > 0.95) {
                const sparkle = document.createElement('div');
                sparkle.style.position = 'fixed';
                sparkle.style.left = e.clientX + 'px';
                sparkle.style.top = e.clientY + 'px';
                sparkle.style.width = '1px';
                sparkle.style.height = '1px';
                sparkle.style.background = 'rgba(255, 255, 255, 0.8)';
                sparkle.style.borderRadius = '50%';
                sparkle.style.pointerEvents = 'none';
                sparkle.style.zIndex = '5';
                sparkle.style.animation = 'fadeOut 1s ease-out forwards';
                
                document.body.appendChild(sparkle);
                
                setTimeout(() => {
                    if (sparkle.parentNode) {
                        sparkle.parentNode.removeChild(sparkle);
                    }
                }, 1000);
            }
        });
    }

    // Animación suave de fade-in para el formulario
    function animateForm() {
        const loginCard = document.querySelector('.login-card');
        loginCard.style.opacity = '0';
        loginCard.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            loginCard.style.transition = 'all 1s ease';
            loginCard.style.opacity = '1';
            loginCard.style.transform = 'translateY(0)';
        }, 500);
    }

    // CSS para animaciones
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            0% { opacity: 1; transform: scale(1); }
            100% { opacity: 0; transform: scale(0); }
        }
    `;
    document.head.appendChild(style);

    // Inicializar al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        createDynamicStars();
        createSubtleCursorEffect();
        animateForm();
    });
</script>

<!-- Script de transiciones de página -->
<script src="assets/js/page-transitions.js"></script>

</body>
</html>
