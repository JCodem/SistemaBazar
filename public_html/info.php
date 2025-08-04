<?php
// info.php - Landing page informativa del sistema
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bienvenido a Sistema Bazar POS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent-primary: #3b82f6;
      --accent-secondary: #8b5cf6;
      --accent-success: #10b981;
      --accent-warning: #f59e0b;
      --accent-danger: #ef4444;
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --bg-card: #1e293b;
      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --shadow: 0 4px 24px rgba(59, 130, 246, 0.15);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
      color: var(--text-primary);
      min-height: 100vh;
      margin: 0;
      overflow-x: hidden;
    }
    /* Animación de fondo con partículas */
    #particles-bg {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      z-index: 0;
      pointer-events: none;
      background: transparent;
    }
    .landing-header {
      text-align: center;
      padding: 5rem 2rem 2rem 2rem;
      background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    .landing-header h1 {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 1rem;
      letter-spacing: 0.02em;
      animation: fadeInDown 1s ease;
    }
    .landing-header p {
      font-size: 1.25rem;
      font-weight: 400;
      margin-bottom: 2rem;
      animation: fadeInUp 1.2s ease;
    }
    .btn-cta {
      background: linear-gradient(135deg, var(--accent-success), var(--accent-primary));
      color: #fff;
      font-weight: 600;
      padding: 1rem 2.5rem;
      border-radius: 32px;
      font-size: 1.2rem;
      border: none;
      box-shadow: var(--shadow);
      transition: var(--transition);
      animation: fadeInUp 1.4s ease;
    }
    .btn-cta:hover {
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-success));
      transform: scale(1.05);
      box-shadow: 0 8px 32px rgba(59,130,246,0.2);
    }
    .arrow-down {
      position: absolute;
      left: 50%;
      bottom: 2rem;
      transform: translateX(-50%);
      font-size: 2.5rem;
      color: #fff;
      opacity: 0.7;
      animation: bounceArrow 1.2s infinite alternate;
    }
    @keyframes bounceArrow {
      from { transform: translateX(-50%) translateY(0); }
      to { transform: translateX(-50%) translateY(16px); }
    }
    .features-section {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2rem;
      padding: 4rem 2rem;
      background: var(--bg-secondary);
      animation: fadeInUp 1.6s ease;
    }
    .feature-card {
      background: var(--bg-card);
      border-radius: 20px;
      box-shadow: var(--shadow);
      padding: 2rem;
      max-width: 350px;
      min-width: 280px;
      color: var(--text-primary);
      text-align: center;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(59,130,246,0.08);
      animation: fadeInUpCard 1.8s cubic-bezier(0.25, 0.8, 0.25, 1);
      opacity: 0;
    }
    .feature-card.visible {
      opacity: 1;
      animation: fadeInUpCard 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    @keyframes fadeInUpCard {
      from { opacity: 0; transform: translateY(40px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .feature-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      color: var(--accent-primary);
      animation: bounce 1.2s infinite alternate;
    }
    .feature-title {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 0.75rem;
      color: var(--accent-secondary);
    }
    .feature-desc {
      font-size: 1rem;
      color: var(--text-secondary);
      margin-bottom: 0.5rem;
    }
    .section-title {
      text-align: center;
      font-size: 2rem;
      font-weight: 700;
      margin: 4rem 0 2rem 0;
      color: var(--accent-primary);
      letter-spacing: 0.01em;
      animation: fadeInDown 1.2s ease;
    }
    .about-section {
      padding: 3rem 2rem;
      background: var(--bg-primary);
      color: var(--text-secondary);
      text-align: center;
      animation: fadeInUp 2s ease;
    }
    .about-section h2 {
      color: var(--accent-primary);
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }
    .about-section p {
      font-size: 1.1rem;
      margin-bottom: 1.5rem;
    }
    .testimonials-section {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2rem;
      padding: 2rem 1rem 4rem 1rem;
      background: var(--bg-secondary);
      animation: fadeInUp 2.1s ease;
    }
    .testimonial-card {
      background: var(--bg-card);
      border-radius: 16px;
      box-shadow: var(--shadow);
      padding: 2rem 1.5rem;
      max-width: 340px;
      color: var(--text-primary);
      text-align: center;
      border: 1px solid rgba(59,130,246,0.08);
      font-size: 1.05rem;
      font-style: italic;
      position: relative;
      margin-bottom: 1rem;
      transition: var(--transition);
    }
    .testimonial-quote {
      margin-bottom: 1rem;
      font-size: 1.1rem;
      color: var(--accent-primary);
    }
    .testimonial-author {
      color: var(--accent-secondary);
      font-weight: 600;
      font-size: 1rem;
    }
    .footer {
      background: var(--bg-secondary);
      color: var(--text-muted);
      text-align: center;
      padding: 2rem 1rem;
      font-size: 0.95rem;
      border-top: 1px solid var(--accent-primary);
      animation: fadeInUp 2.2s ease;
    }
    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-40px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes bounce {
      from { transform: translateY(0); }
      to { transform: translateY(-10px); }
    }
    @media (max-width: 992px) {
      .features-section {
        flex-direction: column;
        align-items: center;
        padding: 2rem 1rem;
      }
      .feature-card {
        max-width: 100%;
      }
    }
    @media (max-width: 768px) {
      .landing-header h1 {
        font-size: 2.2rem;
      }
      .features-section {
        padding: 1rem 0.5rem;
      }
      .about-section {
        padding: 2rem 0.5rem;
      }
    }
  </style>
</head>
<body>
  <!-- Animación de fondo con partículas -->
  <div id="particles-bg"></div>
  <header class="landing-header">
    <h1>Sistema Bazar POS</h1>
    <p>La solución integral para la gestión de ventas, inventario y administración de tu negocio.</p>
  

  </header>

  <section class="features-section">
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
      <div class="feature-title">Punto de Venta (POS)</div>
      <div class="feature-desc">Interfaz rápida y amigable para realizar ventas, gestionar pagos y controlar el flujo de caja en tiempo real.</div>
      <div class="feature-desc">Carrito inteligente, escaneo de productos, y generación de boletas/facturas.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-bar-chart-steps"></i></div>
      <div class="feature-title">Panel de Administración</div>
      <div class="feature-desc">Dashboard moderno con estadísticas, reportes, y control total sobre productos, usuarios y ventas.</div>
      <div class="feature-desc">Gestión de inventario, usuarios, informes diarios y seguridad avanzada.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-person-badge"></i></div>
      <div class="feature-title">Sitio del Vendedor</div>
      <div class="feature-desc">Panel dedicado para vendedores, con acceso a inventario, historial de ventas y control de sesiones de caja.</div>
      <div class="feature-desc">Permisos personalizados y experiencia optimizada para el equipo de ventas.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-shield-lock"></i></div>
      <div class="feature-title">Seguridad y Auditoría</div>
      <div class="feature-desc">Protección de datos, control de accesos, y registro de actividades para máxima tranquilidad.</div>
    </div>
  </section>

  
  <div class="section-title">¿Qué es Sistema Bazar POS?</div>

  <section class="about-section">
    <h2>Un sistema completo para tu negocio</h2>
    <p>Sistema Bazar POS es una plataforma web que integra todo lo necesario para administrar y potenciar tu comercio. Desde el punto de venta hasta el panel de administración y el sitio del vendedor, cada módulo está diseñado para ser intuitivo, seguro y eficiente.</p>
    <p>Con una interfaz moderna, responsiva y animaciones atractivas, podrás gestionar ventas, inventario, usuarios y reportes desde cualquier dispositivo. La paleta de colores y el estilo visual se mantienen coherentes en todo el sistema, brindando una experiencia profesional y agradable.</p>
    <p>¡Descubre cómo Sistema Bazar POS puede transformar la gestión de tu negocio!</p>
      <a href="login.php" class="btn-cta">Comenzar Ahora</a>
  </section>

  <!-- Testimonios -->
  <div class="section-title">Testimonios</div>
  <section class="testimonials-section">
    <div class="testimonial-card">
      <div class="testimonial-quote">“El sistema POS me permitió controlar mi inventario y ventas de forma fácil y rápida. ¡Recomendado!”</div>
      <div class="testimonial-author">— Ana, Tienda El Bazar</div>
    </div>
    <div class="testimonial-card">
      <div class="testimonial-quote">“El panel de administración es súper intuitivo y el soporte técnico excelente.”</div>
      <div class="testimonial-author">— Carlos, Minimarket Central</div>
    </div>
    <div class="testimonial-card">
      <div class="testimonial-quote">“Mis vendedores pueden trabajar desde cualquier dispositivo y el sistema es muy seguro.”</div>
      <div class="testimonial-author">— Marcela, Librería Creativa</div>
    </div>
  </section>

  <footer class="footer">
    &copy; <?= date('Y') ?> Sistema Bazar POS. Todos los derechos reservados.
  </footer>

  <!-- Animaciones Bootstrap Icons -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Particles.js para animación de fondo -->
  <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.11.0/tsparticles.bundle.min.js"></script>
  <script>
    // Animación de fondo con partículas
    tsParticles.load("particles-bg", {
      background: { color: { value: "transparent" } },
      fpsLimit: 60,
      particles: {
        number: { value: 40 },
        color: { value: ["#3b82f6", "#8b5cf6", "#10b981", "#f59e0b"] },
        shape: { type: "circle" },
        opacity: { value: 0.25 },
        size: { value: { min: 2, max: 6 } },
        move: { enable: true, speed: 1.5, direction: "none", outModes: { default: "bounce" } },
        links: { enable: true, color: "#64748b", distance: 120, opacity: 0.15, width: 1 },
      },
      detectRetina: true
    });

    // Animación de entrada para las feature cards
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.feature-card');
      cards.forEach((card, i) => {
        setTimeout(() => card.classList.add('visible'), 200 + i * 180);
      });
    });
  </script>
</body>
</html>
