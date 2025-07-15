<!-- Búsqueda de Productos / Escáner de Códigos -->
<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5><i class="bi bi-upc-scan"></i> Búsqueda de Productos</h5>
    </div>
    <div class="card-body">
        <!-- Campo de búsqueda principal -->
        <div class="input-group mb-3">
            <input type="text" class="form-control form-control-lg" id="product-search" 
                   placeholder="Escanear código de barras/QR o ingresar SKU (ej: coca, test)" autofocus>
            <button class="btn btn-outline-primary" type="button" id="search-btn">
                <i class="bi bi-search"></i> Buscar
            </button>
        </div>
        
        <!-- Controles del escáner -->
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-sm btn-outline-success" id="toggle-barcode-scanner">
                <i class="bi bi-upc-scan"></i> Activar Escáner
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="manual-entry">
                <i class="bi bi-keyboard"></i> Entrada Manual
            </button>
        </div>
        
        <div class="form-text">
            <i class="bi bi-info-circle"></i> 
            Escanea directamente códigos de barras/QR o ingresa manualmente el SKU del producto
        </div>
        
        <!-- Resultados de búsqueda manual -->
        <div id="product-results" class="row mt-3"></div>
    </div>
</div>

<!-- Carrito de Venta -->
<div class="card mb-3">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5><i class="bi bi-cart3"></i> Carrito de Venta</h5>
        <span class="badge bg-light text-dark" id="cart-count">0 items</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm" id="cart-table">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th width="80">Cant</th>
                        <th width="80">Precio</th>
                        <th width="80">Total</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody id="cart-items">
                    <!-- Los items del carrito aparecerán aquí -->
                </tbody>
                <tfoot class="table-secondary">
                    <tr>
                        <th colspan="3" class="text-end fw-bold">TOTAL GENERAL:</th>
                        <th id="cart-total" class="fw-bold">$0</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Área de acciones del carrito -->
        <div class="p-3 bg-light">
            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm" id="clear-cart">
                    <i class="bi bi-trash"></i> Limpiar Carrito
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="save-cart">
                    <i class="bi bi-save"></i> Guardar Carrito
                </button>
            </div>
        </div>
    </div>
</div>
