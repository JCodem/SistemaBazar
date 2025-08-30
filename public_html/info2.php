<?php
// info2.php - Landing page mejorada y visualmente impactante para Sistema Bazar POS
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Descubre Sistema Bazar POS</title>
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
    #particles-bg {
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      z-index: 0;
      pointer-events: none;
      background: transparent;
    }
    .hero-section {
      position: relative;
      z-index: 1;
      text-align: center;
      padding: 6rem 2rem 3rem 2rem;
      background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
      color: #fff;
      overflow: hidden;
    }
    .hero-title {
      font-size: 3.2rem;
      font-weight: 800;
      margin-bottom: 1.2rem;
      letter-spacing: 0.03em;
      animation: fadeInDown 1s ease;
    }
    .hero-subtitle {
      font-size: 1.5rem;
      font-weight: 400;
      margin-bottom: 2.2rem;
      animation: fadeInUp 1.2s ease;
    }
    .hero-img {
      width: 320px;
      max-width: 90vw;
      margin: 0 auto 2rem auto;
      border-radius: 24px;
      box-shadow: 0 8px 32px rgba(59,130,246,0.18);
      animation: fadeInUp 1.4s ease;
      background: #fff;
      object-fit: cover;
      border: 4px solid var(--accent-primary);
    }
    .btn-cta {
      background: linear-gradient(135deg, var(--accent-success), var(--accent-primary));
      color: #fff;
      font-weight: 700;
      padding: 1.1rem 2.7rem;
      border-radius: 32px;
      font-size: 1.3rem;
      border: none;
      box-shadow: var(--shadow);
      transition: var(--transition);
      animation: fadeInUp 1.6s ease;
    }
    .btn-cta:hover {
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-success));
      transform: scale(1.07);
      box-shadow: 0 12px 40px rgba(59,130,246,0.22);
    }
    .features-section {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 2.5rem;
      padding: 4rem 2rem 2rem 2rem;
      background: var(--bg-secondary);
      animation: fadeInUp 1.8s ease;
    }
    .feature-card {
      background: var(--bg-card);
      border-radius: 20px;
      box-shadow: var(--shadow);
      padding: 2.2rem 1.5rem;
      max-width: 320px;
      min-width: 240px;
      color: var(--text-primary);
      text-align: center;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(59,130,246,0.10);
      animation: fadeInUpCard 1.9s cubic-bezier(0.25, 0.8, 0.25, 1);
      opacity: 0;
    }
    .feature-card.visible {
      opacity: 1;
      animation: fadeInUpCard 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .feature-icon {
      font-size: 3rem;
      margin-bottom: 1.2rem;
      color: var(--accent-primary);
      animation: bounce 1.2s infinite alternate;
    }
    .feature-title {
      font-size: 1.25rem;
      font-weight: 700;
      margin-bottom: 0.7rem;
      color: var(--accent-secondary);
    }
    .feature-desc {
      font-size: 1.05rem;
      color: var(--text-secondary);
      margin-bottom: 0.5rem;
    }
    .demo-section {
      background: var(--bg-primary);
      padding: 3.5rem 2rem 2rem 2rem;
      text-align: center;
      color: var(--text-secondary);
      animation: fadeInUp 2.1s ease;
    }
    .demo-title {
      font-size: 2.1rem;
      font-weight: 700;
      color: var(--accent-primary);
      margin-bottom: 1.2rem;
      animation: fadeInDown 1.2s ease;
    }
    .demo-img {
      width: 340px;
      max-width: 95vw;
      border-radius: 18px;
      box-shadow: 0 6px 24px rgba(59,130,246,0.13);
      margin-bottom: 1.5rem;
      border: 3px solid var(--accent-secondary);
      background: #fff;
      object-fit: cover;
      animation: fadeInUp 1.3s ease;
    }
    .demo-btn {
      background: linear-gradient(135deg, var(--accent-warning), var(--accent-primary));
      color: #fff;
      font-weight: 600;
      padding: 0.9rem 2.2rem;
      border-radius: 28px;
      font-size: 1.1rem;
      border: none;
      box-shadow: var(--shadow);
      transition: var(--transition);
      animation: fadeInUp 1.5s ease;
    }
    .demo-btn:hover {
      background: linear-gradient(135deg, var(--accent-primary), var(--accent-warning));
      transform: scale(1.05);
      box-shadow: 0 8px 32px rgba(59,130,246,0.18);
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
    @keyframes fadeInUpCard {
      from { opacity: 0; transform: translateY(40px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
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
      .demo-img, .hero-img {
        width: 90vw;
      }
    }
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.2rem;
      }
      .features-section {
        padding: 1rem 0.5rem;
      }
      .demo-section {
        padding: 2rem 0.5rem;
      }
    }
  </style>
</head>
<body>
  <div id="particles-bg"></div>
  <!-- Navbar responsivo -->
  <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); box-shadow: var(--shadow);">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#">
        <span style="font-weight:800; font-size:1.5rem; letter-spacing:0.04em; color:#fff;">Prisma-pos</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link active" href="#hero">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="#features">Ventajas</a></li>
          <li class="nav-item"><a class="nav-link" href="#demo">Demo</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Ingresar</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="hero-section" id="hero">
    <img src="https://images.unsplash.com/photo-1515378791036-0648a3ef77b2?auto=format&fit=crop&w=800&q=80" alt="POS moderno" class="hero-img" />
    <div class="hero-title">Sistema Bazar POS</div>
    <div class="hero-subtitle">Gestiona tu negocio con estilo, rapidez y seguridad.</div>
    <a href="login.php" class="btn-cta">¡Probar Ahora!</a>
  </section>

  <section class="features-section" id="features">
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
      <div class="feature-title">Ventas Rápidas</div>
      <div class="feature-desc">Realiza ventas en segundos con una interfaz intuitiva.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-bar-chart-steps"></i></div>
      <div class="feature-title">Panel Moderno</div>
      <div class="feature-desc">Control total de inventario y estadísticas visuales.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-person-badge"></i></div>
      <div class="feature-title">Vendedores Felices</div>
      <div class="feature-desc">Panel dedicado y experiencia optimizada para tu equipo.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon"><i class="bi bi-shield-lock"></i></div>
      <div class="feature-title">Seguridad Total</div>
      <div class="feature-desc">Tus datos protegidos y acceso seguro.</div>
    </div>
  </section>

  <section class="demo-section" id="demo">
    <div class="demo-title">¡Mira cómo funciona!</div>
    <img src="https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=800&q=80" alt="Demo Sistema Bazar POS" class="demo-img" />
    <br>
    <a href="login.php" class="demo-btn">Ver Demo</a>
  </section>

  <footer class="footer">
    &copy; <?= date('Y') ?> Sistema Bazar POS. Todos los derechos reservados.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/tsparticles@2.11.0/tsparticles.bundle.min.js"></script>
  <script>
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
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.feature-card');
      cards.forEach((card, i) => {
        setTimeout(() => card.classList.add('visible'), 200 + i * 180);
      });
    });
  </script>
</body>
</html>
