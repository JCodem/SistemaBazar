<?php
require_once '../../includes/layout_admin.php';
require_once '../../includes/db.php';
require_once '../../includes/funciones.php';

// Manejar POST para actualizar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    // Actualizar IVA
    if (isset($_POST['iva'])) {
        $iva = filter_var($_POST['iva'], FILTER_VALIDATE_FLOAT);
        if ($iva !== false && $iva >= 0 && $iva <= 1) {
            $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'iva'");
            $stmt->execute([$iva]);
            $success = "Tasa de IVA actualizada correctamente.";
        } else {
            $error = "Valor de IVA inválido. Debe ser un número entre 0 y 1 (ej: 0.19).";
        }
    }
}

// Obtener configuración actual
$configStmt = $conn->query("SELECT clave, valor FROM configuracion");
$config = $configStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$iva_actual = $config['iva'] ?? '0.19';
?>

<div class="container mt-4">
    <h2>Seguridad y Configuración</h2>
    <p>Ajustes generales del sistema.</p>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            Configuración de Impuestos
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <div class="mb-3">
                    <label for="iva" class="form-label">Tasa de IVA (ej: 0.19 para 19%)</label>
                    <input type="number" step="0.01" min="0" max="1" class="form-control" id="iva" name="iva" value="<?= htmlspecialchars($iva_actual) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Guardar Configuración</button>
            </form>
        </div>
    </div>
</div>
