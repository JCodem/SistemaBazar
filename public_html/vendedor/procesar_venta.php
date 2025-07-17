<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_middleware.php';
require_once '../../includes/rol_middleware_vendedor.php';

$producto_id = $_POST['producto_id'] ?? null;
$cantidad = $_POST['cantidad'] ?? null;
$precio_unitario = $_POST['precio_unitario'] ?? null;
$vendedor_id = $_SESSION['usuario']['id'] ?? null;
$fecha_venta = date('Y-m-d H:i:s');

if ($producto_id && $cantidad && $precio_unitario && $vendedor_id) {
    try {
        $stmt = $conn->prepare("INSERT INTO ventas (producto_id, cantidad, precio_unitario, vendedor_id, fecha_venta)
                                VALUES (:producto_id, :cantidad, :precio_unitario, :vendedor_id, :fecha_venta)");
        $stmt->execute([
            ':producto_id' => $producto_id,
            ':cantidad' => $cantidad,
            ':precio_unitario' => $precio_unitario,
            ':vendedor_id' => $vendedor_id,
            ':fecha_venta' => $fecha_venta
        ]);

        $_SESSION['mensaje'] = "Venta registrada correctamente.";
        header('Location: dashboard.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al registrar venta: " . $e->getMessage();
        header('Location: venta.php');
        exit;
    }
} else {
    $_SESSION['error'] = "Por favor complete todos los campos.";
    header('Location: venta.php');
    exit;
}
