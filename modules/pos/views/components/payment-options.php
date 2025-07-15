<!-- Resumen de Venta Unificado -->
<div class="card mb-3">
    <div class="card-header bg-success text-white">
        <h5><i class="bi bi-receipt"></i> Resumen de Venta</h5>
    </div>
    <div class="card-body">
        <!-- Información del Resumen -->
        <div class="row text-center mb-4">
            <div class="col-6">
                <h6 class="text-muted">Items</h6>
                <h4 id="summary-items" class="text-primary">0</h4>
            </div>
            <div class="col-6">
                <h6 class="text-muted">Total</h6>
                <h4 id="summary-total" class="text-success">$0</h4>
            </div>
        </div>
        
        <hr class="my-3">
        
        <!-- Método de Pago -->
        <div class="mb-3">
            <label for="payment-method" class="form-label fw-bold">
                <i class="bi bi-credit-card-2-front"></i> Método de Pago
            </label>
            <select class="form-select form-select-lg" id="payment-method">
                <option value="efectivo">💵 Efectivo</option>
                <option value="tarjeta">💳 Tarjeta de Crédito/Débito</option>
                <option value="transferencia">🏦 Transferencia Bancaria</option>
            </select>
        </div>
        
        <!-- Panel de Pago en Efectivo -->
        <div id="cash-payment" class="payment-detail">
            <div class="mb-3">
                <label for="amount-received" class="form-label fw-bold">Monto Recibido</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control" id="amount-received" min="0" step="1" placeholder="0">
                </div>
            </div>
            <div class="mb-3">
                <label for="change-amount" class="form-label fw-bold">Cambio a Devolver</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-info text-white">$</span>
                    <input type="text" class="form-control bg-light" id="change-amount" readonly value="0">
                </div>
            </div>
        </div>
        
        <!-- Panel de Pago con Tarjeta -->
        <div id="card-payment" class="payment-detail" style="display: none;">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Pago con Tarjeta</strong><br>
                Procese el pago en el terminal bancario y confirme la transacción.
            </div>
        </div>
        
        <!-- Panel de Transferencia -->
        <div id="transfer-payment" class="payment-detail" style="display: none;">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <strong>Transferencia Bancaria</strong><br>
                Verifique que la transferencia haya sido completada antes de finalizar.
            </div>
        </div>
        
        <!-- Botón de Completar Venta -->
        <button class="btn btn-success btn-lg w-100 mt-3" id="complete-sale" disabled>
            <i class="bi bi-check-circle"></i> Completar Venta
        </button>
        
        <!-- Botones de Acción Rápida -->
        <div class="row mt-3">
            <div class="col-6">
                <button class="btn btn-outline-primary btn-sm w-100" id="quick-payment">
                    <i class="bi bi-lightning"></i> Pago Exacto
                </button>
            </div>
            <div class="col-6">
                <button class="btn btn-outline-secondary btn-sm w-100" id="new-sale">
                    <i class="bi bi-plus-circle"></i> Nueva Venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Acciones Adicionales -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h6><i class="bi bi-gear"></i> Acciones Adicionales</h6>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2">
            <button class="btn btn-outline-info btn-sm" id="hold-sale">
                <i class="bi bi-pause-circle"></i> Suspender Venta
            </button>
            <button class="btn btn-outline-warning btn-sm" id="discount-sale">
                <i class="bi bi-percent"></i> Aplicar Descuento
            </button>
            <button class="btn btn-outline-danger btn-sm" id="cancel-sale">
                <i class="bi bi-x-circle"></i> Cancelar Venta
            </button>
        </div>
    </div>
</div>
