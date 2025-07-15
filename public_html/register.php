<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro de Usuario</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <?php if (isset($_SESSION['registro_exito'])): ?>
    <!-- Redirigir automáticamente después del registro -->
    <meta http-equiv="refresh" content="3;url=login.php">
  <?php endif; ?>
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

<div class="card shadow p-4 w-100" style="max-width: 500px;">
  <h3 class="text-center mb-4">Registro de Usuario</h3>

  <?php if (isset($_SESSION['registro_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['registro_error'] ?></div>
    <?php unset($_SESSION['registro_error']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['registro_exito'])): ?>
    <div class="alert alert-success"><?= $_SESSION['registro_exito'] ?><br>Redirigiendo al login...</div>
    <?php unset($_SESSION['registro_exito']); ?>
  <?php endif; ?>

  <form action="procesar_registro.php" method="POST">
    <div class="mb-3">
      <label for="correo" class="form-label">Correo electrónico</label>
      <input type="email" name="correo" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="contraseña" class="form-label">Contraseña</label>
      <input type="password" name="contraseña" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="nombre" class="form-label">Nombre</label>
      <input type="text" name="nombre" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="rut" class="form-label">RUT</label>
      <input type="text" name="rut" class="form-control" required>
    </div>

    <div class="mb-4">
      <label for="rol" class="form-label">Rol</label>
      <select name="rol" class="form-select" required>
        <option value="vendedor">Vendedor</option>
        <option value="jefe">Jefe</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary w-100">Registrar</button>
  </form>

  <div class="text-center mt-3">
    <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
  </div>
</div>

</body>
</html>
