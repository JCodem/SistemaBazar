<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login - Sistema Bazar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <h2 class="mb-4 text-center">Inicio de sesión</h2>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <form action="auth.php" method="POST" class="shadow p-4 rounded bg-white">
    <div class="mb-3">
      <label for="correo" class="form-label">Correo</label>
      <input type="email" name="correo" class="form-control" required>
    </div>
    <div class="mb-3">
      <label for="contraseña" class="form-label">Contraseña</label>
      <input type="password" name="contraseña" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
  </form>
</div>

</body>
</html>
