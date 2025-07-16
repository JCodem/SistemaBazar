<?php
require_once '../../includes/layout_vendedor.php';
require_once '../../includes/auth_middleware.php';
require_once '../../includes/rol_middleware_vendedor.php';
?>

<h2 class="mb-4">📝 Registrar Nueva Venta</h2>

<form action="procesar_venta.php" method="POST">
  <div class="mb-3">
    <label for="producto_id" class="form-label">Producto</label>
    <select name="producto_id" id="producto_id" class="form-select" required>
      <option value="">Seleccione un producto</option>
      <?php
      require_once '../../includes/db.php';
      $stmt = $conn->query("SELECT id, nombre FROM productos");
      while ($producto = $stmt->fetch(PDO::FETCH_ASSOC)) {
          echo "<option value='{$producto['id']}'>{$producto['nombre']}</option>";
      }
      ?>
    </select>
  </div>

  <div class="mb-3">
    <label for="cantidad" class="form-label">Cantidad</label>
    <input type="number" name="cantidad" id="cantidad" class="form-control" required min="1">
  </div>

  <div class="mb-3">
    <label for="precio_unitario" class="form-label">Precio Unitario</label>
    <input type="number" name="precio_unitario" id="precio_unitario" class="form-control" required step="0.01" min="0">
  </div>

  <button type="submit" class="btn btn-primary">Registrar Venta</button>
</form>
