<!-- Sistema Unificado: Búsqueda y Carrito -->
<div class="pos-card fade-in">
    <div class="pos-card-header">
        <h5 class="mb-0">
            <i class="bi bi-cart3"></i>
            Sistema de Ventas
        </h5>
    </div>
    <div class="card-body p-4">
        <!-- Campo de búsqueda unificado -->
        <div class="row g-3 mb-4">
            <div class="col-md-10">
                <div class="position-relative">
                    <input type="text" 
                           id="product-search" 
                           class="pos-input pos-input-lg w-100" 
                           placeholder="Buscar producto por nombre, código o SKU..."
                           autocomplete="off"
                           autofocus>
                    <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                </div>
            </div>
            <div class="col-md-2">
                <button type="button" 
                        id="toggle-barcode-scanner" 
                        class="pos-btn pos-btn-outline pos-btn-lg w-100">
                    <i class="bi bi-upc-scan"></i>
                </button>
            </div>
        </div>

        <!-- Resultados de búsqueda desplegable -->
        <div id="search-dropdown" class="search-dropdown" style="display: none;">
            <div class="search-dropdown-content">
                <div id="search-results-list"></div>
            </div>
        </div>
        
        <!-- Carrito de Compras -->
        <div class="cart-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold text-success">
                    <i class="bi bi-cart-check"></i>
                    Productos en el Carrito
                </h6>
                <div class="pos-badge pos-badge-success">
                    <span id="cart-count">0</span> artículos
                </div>
            </div>
            
            <!-- Tabla del carrito -->
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table pos-table mb-0" id="cart-table">
                    <thead class="sticky-top">
                        <tr>
                            <th style="min-width: 200px;">Producto</th>
                            <th style="width: 80px;" class="text-center">Cant.</th>
                            <th style="width: 100px;" class="text-end">Precio</th>
                            <th style="width: 100px;" class="text-end">Total</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="cart-items">
                        <tr id="empty-cart-row">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-cart-x fs-1 d-block mb-2 opacity-50"></i>
                                <div>El carrito está vacío</div>
                                <small>Busca productos para añadir al carrito</small>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end fw-bold fs-5">TOTAL GENERAL:</th>
                            <th id="cart-total" class="fw-bold fs-5 text-success">$0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Acciones del carrito -->
            <div class="p-3 border-top" style="background: linear-gradient(135deg, #f8fafc, #f1f5f9);">
                <div class="d-flex gap-2">
                    <button type="button" 
                            id="clear-cart" 
                            class="pos-btn pos-btn-outline flex-fill"
                            style="border-color: var(--danger-color); color: var(--danger-color);">
                        <i class="bi bi-trash"></i>
                        Limpiar Carrito
                    </button>
                    <button type="button" 
                            id="save-cart" 
                            class="pos-btn pos-btn-outline flex-fill">
                        <i class="bi bi-bookmark"></i>
                        Guardar Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dropdown de búsqueda */
.search-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    margin-top: 4px;
}

.search-dropdown-content {
    background: var(--light-color);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: var(--shadow-heavy);
    max-height: 300px;
    overflow-y: auto;
}

.search-result-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background: rgba(37, 99, 235, 0.05);
}

.search-result-item.selected {
    background: rgba(37, 99, 235, 0.1);
    border-left: 4px solid var(--primary-color);
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.product-details {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.product-price {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--success-color);
}

.product-stock {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 32px;
    height: 32px;
    border: 1px solid var(--border-color);
    background: var(--light-color);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: var(--text-primary);
}

.quantity-btn:hover {
    background: var(--secondary-color);
    border-color: var(--primary-color);
}

.quantity-input {
    width: 60px;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 0.25rem;
    background: var(--light-color);
}

.quantity-input:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

/* Animación para items agregados */
.cart-item-added {
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Loading state */
.loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid var(--border-color);
    border-radius: 50%;
    border-top-color: var(--primary-color);
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Estado de búsqueda activa */
.search-active .pos-input {
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
}

/* Indicador de no resultados */
.no-results {
    padding: 2rem;
    text-align: center;
    color: var(--text-secondary);
}

.no-results i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}
</style>
