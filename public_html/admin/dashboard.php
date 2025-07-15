<?php require_once '../../includes/layout_admin.php'; ?>

<h2>¡Bienvenido, <?= htmlspecialchars($_SESSION['usuario']['nombre']) ?>!</h2>
<p class="mb-4">Este es tu <strong>panel de administrador</strong>.</p>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow-sm p-3 bg-white">
            <h5>Total Ventas Hoy</h5>
            <p>$0.00</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm p-3 bg-white">
            <h5>Vendedores Activos</h5>
            <p>0</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm p-3 bg-white">
            <h5>Productos en Stock</h5>
            <p>0</p>
        </div>
    </div>
</div>


