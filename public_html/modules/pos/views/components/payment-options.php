<!-- Panel de Finalización de Compra -->
<div class="pos-card fade-in mb-4">
    <div class="pos-card-header info">
        <h5 class="mb-0">
            <i class="bi bi-credit-card"></i>
            Finalizar Compra
        </h5>
    </div>
    <div class="card-body p-4">
        <!-- Resumen de totales -->
        <div class="mb-4">
            <div class="row g-3">
                <div class="col-6">
                    <div class="stats-card">
                        <div class="stats-number text-primary" id="summary-items">0</div>
                        <div class="stats-label">Artículos</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stats-card">
                        <div class="stats-number text-warning" id="final-tax">$0.00</div>
                        <div class="stats-label">Impuesto</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-3 p-3 rounded-3" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">Total a Pagar:</span>
                    <span class="fs-3 fw-bold" id="summary-total">$0.00</span>
                </div>
            </div>
        </div>

        <!-- Métodos de pago -->
        <div class="mb-4">
            <h6 class="mb-3 fw-bold text-primary">Método de Pago</h6>
            <div class="d-grid gap-2">
                <label class="payment-method-card">
                    <input type="radio" name="payment_method" value="efectivo" id="payment-method" checked>
                    <div class="payment-method-content">
                        <i class="bi bi-cash-coin fs-3 text-success"></i>
                        <div>
                            <div class="fw-bold">Efectivo</div>
                            <small class="text-muted">Pago en efectivo</small>
                        </div>
                    </div>
                </label>
                
                <label class="payment-method-card">
                    <input type="radio" name="payment_method" value="tarjeta">
                    <div class="payment-method-content">
                        <i class="bi bi-credit-card fs-3 text-primary"></i>
                        <div>
                            <div class="fw-bold">Tarjeta</div>
                            <small class="text-muted">Débito o crédito</small>
                        </div>
                    </div>
                </label>
                
                <label class="payment-method-card">
                    <input type="radio" name="payment_method" value="transferencia">
                    <div class="payment-method-content">
                        <i class="bi bi-bank fs-3 text-info"></i>
                        <div>
                            <div class="fw-bold">Transferencia</div>
                            <small class="text-muted">Transferencia bancaria</small>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Pago en efectivo -->
        <div id="cash-payment" class="mb-4">
            <h6 class="mb-3 fw-bold text-success">Pago en Efectivo</h6>
            <div class="mb-3">
                <label class="form-label fw-semibold">Monto Recibido</label>
                <input type="number" 
                       id="amount-received" 
                       class="pos-input pos-input-lg w-100" 
                       placeholder="0.00" 
                       step="0.01"
                       min="0">
            </div>
            
            <!-- Cambio -->
            <div class="p-3 rounded-3" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2);">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-success">Cambio a Devolver:</span>
                    <span class="fs-4 fw-bold text-success" id="change-amount">$0.00</span>
                </div>
            </div>
        </div>

        <!-- Panel de Pago con Tarjeta -->
        <div id="card-payment" class="payment-detail mb-4" style="display: none;">
            <div class="p-3 rounded-3" style="background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.2);">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-credit-card fs-3 text-primary"></i>
                    <div>
                        <div class="fw-bold text-primary">Pago con Tarjeta</div>
                        <small class="text-muted">Procese el pago en el terminal bancario y confirme la transacción</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel de Transferencia -->
        <div id="transfer-payment" class="payment-detail mb-4" style="display: none;">
            <div class="p-3 rounded-3" style="background: rgba(6, 182, 212, 0.1); border: 1px solid rgba(6, 182, 212, 0.2);">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-bank fs-3 text-info"></i>
                    <div>
                        <div class="fw-bold text-info">Transferencia Bancaria</div>
                        <small class="text-muted">Verifique que la transferencia haya sido completada antes de finalizar</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- RUT del cliente -->
        <div class="mb-4">
            <h6 class="mb-3 fw-bold text-primary">RUT del Cliente (Opcional)</h6>
            <div class="row g-3">
                <div class="col-12">
                    <input type="text" 
                           id="customer-rut" 
                           class="pos-input w-100" 
                           placeholder="Ingrese RUT del cliente (ej: 12.345.678-9)">
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="d-grid gap-2">
            <button type="button" 
                    id="complete-sale" 
                    class="pos-btn pos-btn-success pos-btn-lg"
                    disabled>
                <i class="bi bi-check-circle"></i>
                Completar Venta
            </button>
            
            <div class="row g-2 mt-2">
                <div class="col-6">
                    <button type="button" 
                            id="quick-payment" 
                            class="pos-btn pos-btn-outline w-100">
                        <i class="bi bi-lightning"></i>
                        Exacto
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" 
                            id="new-sale" 
                            class="pos-btn pos-btn-outline w-100">
                        <i class="bi bi-plus-circle"></i>
                        Nueva
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Acciones Adicionales -->
<div class="pos-card fade-in">
    <div class="pos-card-header warning">
        <h6 class="mb-0">
            <i class="bi bi-gear"></i>
            Acciones Adicionales
        </h6>
    </div>
    <div class="card-body p-4">
        <div class="d-grid gap-2">
            <button type="button" 
                    id="hold-sale" 
                    class="pos-btn pos-btn-outline">
                <i class="bi bi-pause-circle"></i>
                Suspender Venta
            </button>
            <button type="button" 
                    id="discount-sale" 
                    class="pos-btn pos-btn-outline">
                <i class="bi bi-percent"></i>
                Aplicar Descuento
            </button>
            <button type="button" 
                    id="cancel-sale" 
                    class="pos-btn pos-btn-outline"
                    style="border-color: var(--danger-color); color: var(--danger-color);">
                <i class="bi bi-x-circle"></i>
                Cancelar Venta
            </button>
        </div>
    </div>
</div>

<style>
/* Estilos para métodos de pago */
.payment-method-card {
    display: block;
    cursor: pointer;
    margin: 0;
}

.payment-method-card input[type="radio"] {
    display: none;
}

.payment-method-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    background: var(--light-color);
    transition: all 0.3s ease;
}

.payment-method-card:hover .payment-method-content {
    border-color: var(--primary-color);
    background: rgba(37, 99, 235, 0.05);
}

.payment-method-card input[type="radio"]:checked + .payment-method-content {
    border-color: var(--primary-color);
    background: rgba(37, 99, 235, 0.1);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Botones de denominaciones */
.denomination-btn {
    font-weight: 600;
    font-size: 0.875rem;
}

.denomination-btn:hover {
    background: var(--primary-color) !important;
    color: white !important;
    border-color: var(--primary-color) !important;
}

/* Ocultamiento condicional de secciones de pago */
#card-payment.payment-detail,
#transfer-payment.payment-detail {
    display: none;
}

/* Animaciones para el cambio */
#change-amount {
    transition: all 0.3s ease;
}

.change-positive {
    color: var(--success-color) !important;
}

.change-negative {
    color: var(--danger-color) !important;
}

/* Estados del botón de procesar venta */
#complete-sale:disabled {
    background: var(--border-color) !important;
    color: var(--text-secondary) !important;
    cursor: not-allowed;
}

#complete-sale:not(:disabled):hover {
    background: linear-gradient(135deg, #047857, #10b981) !important;
    transform: translateY(-1px);
    box-shadow: var(--shadow-medium);
}

/* Responsive */
@media (max-width: 768px) {
    .denomination-btn {
        font-size: 0.75rem;
        padding: 0.5rem;
    }
    
    .stats-card {
        padding: 1rem;
    }
    
    .stats-number {
        font-size: 1.5rem;
    }
}
</style>
