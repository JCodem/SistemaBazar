<?php
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';
require_once '../../includes/funciones.php'; // CSRF helpers if needed

$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;
if (!$sesion_id) {
    echo "<p>ID de sesión inválido.</p>";
    exit;
}

// Obtener información de la sesión y vendedor
$sesionStmt = $conn->prepare(
    "SELECT sc.*, u.nombre as vendedor FROM sesiones_caja sc JOIN usuarios u ON sc.usuario_id = u.id WHERE sc.id = ?"
);
$sesionStmt->execute([$sesion_id]);
$sesion = $sesionStmt->fetch(PDO::FETCH_ASSOC);
if (!$sesion) {
    echo "<p>Sesión no encontrada.</p>";
    exit;
}

// Obtener ventas de la sesión
$ventasStmt = $conn->prepare(
    "SELECT v.id as boleta_id, v.fecha, v.total, v.tipo_documento, v.numero_documento
     FROM ventas v
     WHERE DATE(v.fecha) = DATE(?) AND v.usuario_id = ?
     ORDER BY v.fecha"
);
$ventasStmt->execute([
    $sesion['fecha_apertura'],
    $sesion['usuario_id']
]);
$ventas = $ventasStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Ventas de la sesión de caja</h2>
    <p><strong>Vendedor:</strong> <?= htmlspecialchars($sesion['vendedor']) ?></p>
    <p><strong>Fecha Apertura:</strong> <?= date('d/m/Y H:i', strtotime($sesion['fecha_apertura'])) ?></p>
    <?php if ($sesion['fecha_cierre']): ?>
        <p><strong>Fecha Cierre:</strong> <?= date('d/m/Y H:i', strtotime($sesion['fecha_cierre'])) ?></p>
    <?php endif; ?>
    <hr>

    <?php if (empty($ventas)): ?>
        <p>No hay ventas para esta sesión.</p>
    <?php else: ?>
        <?php foreach ($ventas as $venta): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <strong>Boleta #<?= $venta['boleta_id'] ?></strong> - Fecha: <?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?>
                    <br>
                    <small>Tipo: <?= htmlspecialchars($venta['tipo_documento']) ?> | N° Doc: <?= htmlspecialchars($venta['numero_documento']) ?></small>
                    <span class="float-end">Total: $<?= number_format($venta['total'],2,',','.') ?></span>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Detalle de Productos</h5>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Obtener detalles de la venta
                            $detStmt = $conn->prepare(
                                "SELECT p.nombre, vd.cantidad, vd.precio_unitario, vd.subtotal
                                 FROM venta_detalles vd JOIN productos p ON vd.producto_id = p.id
                                 WHERE vd.venta_id = ?"
                            );
                            $detStmt->execute([$venta['boleta_id']]);
                            $detalles = $detStmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($detalles as $det): ?>
                                <tr>
                                    <td><?= htmlspecialchars($det['nombre']) ?></td>
                                    <td><?= $det['cantidad'] ?></td>
                                    <td>$<?= number_format($det['precio_unitario'],2,',','.') ?></td>
                                    <td>$<?= number_format($det['subtotal'],2,',','.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="apertura_cierre.php" class="btn btn-secondary">Volver</a>
</div>
