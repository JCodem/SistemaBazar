<?php // Vista parcial POS cargada vía layout_vendedor con middleware_unificado para autenticación y rol.

// Usar las variables de sesión user_nombre si están disponibles, sino usar usuario['nombre']
if (isset($_SESSION['user_nombre'])) {
    $nombre = htmlspecialchars($_SESSION['user_nombre']);
} elseif (isset($_SESSION['usuario']['nombre'])) {
    $nombre = htmlspecialchars($_SESSION['usuario']['nombre']);
} else {
    $nombre = 'Vendedor';
}
?>

<?php // VISTA PARCIAL POS: se carga dentro del layout_vendedor.php. HTML y head removidos. ?>

<!-- POS Stylesheets -->
<link rel="stylesheet" href="./assets/css/pos-styles.css">

<!-- Header -->
<nav class="navbar navbar-expand-lg" style="background: var(--light-color); border-bottom: 1px solid var(--border-color); box-shadow: var(--shadow-light);">
  <div class="container-fluid">
    <div class="navbar-brand fw-bold text-primary">
      <i class="bi bi-shop"></i>
      Punto de Venta
    </div>
    
    <div class="navbar-nav ms-auto">
      <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle"></i>
          <span><?php echo $nombre; ?></span>
        </a>
        <ul class="dropdown-menu">
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="/SistemaBazar/public_html/logout.php">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
          </a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

  <!-- Main Content -->
  <div class="container-fluid py-4">
    <div class="pos-container">
      <!-- Main Area: Search + Cart (70%) -->
      <div class="pos-main-area">
        <div class="pos-card fade-in">
          <div class="pos-card-header">
            <h5>
              <i class="bi bi-cart3"></i>
              Sistema de Ventas
            </h5>
          </div>
          <div class="pos-card-body">
            <!-- Search Section -->
            <div class="search-section">
              <div class="search-container">
                <div class="search-input-container">
                  <input type="text" 
                         id="product-search" 
                         class="pos-input pos-input-lg" 
                         placeholder="Buscar producto por nombre, código o SKU..."
                         autocomplete="off"
                         autofocus>
                  <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                  
                  <!-- Search Dropdown -->
                  <div id="search-dropdown" class="search-dropdown" style="display: none;">
                    <div class="search-dropdown-content">
                      <div id="search-results-list"></div>
                    </div>
                  </div>
                </div>
                <button type="button" 
                        id="clear-cart" 
                        class="pos-btn pos-btn-outline">
                  <i class="bi bi-trash"></i>
                  Limpiar
                </button>
              </div>
            </div>
            
            <!-- Cart Section -->
            <div class="cart-section">
              <div class="cart-header">
                <div class="cart-title">
                  <i class="bi bi-cart-check"></i>
                  Productos en el Carrito
                </div>
                <div class="cart-summary">
                  <span id="cart-count">0</span> artículos - <span id="cart-total">$0.00</span>
                </div>
              </div>
              
              <div class="pos-table-container">
                <div class="pos-table">
                  <table>
                    <thead>
                      <tr>
                        <th style="width: 40%;">Producto</th>
                        <th style="width: 20%;" class="text-center">Cantidad</th>
                        <th style="width: 15%;" class="text-end">Precio</th>
                        <th style="width: 15%;" class="text-end">Total</th>
                        <th style="width: 10%;" class="text-center">Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="cart-items">
                      <tr id="empty-cart-row">
                        <td colspan="5">
                          <div class="empty-cart">
                            <i class="bi bi-cart-x"></i>
                            <div><strong>El carrito está vacío</strong></div>
                            <small>Busca productos para añadir al carrito</small>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Checkout Area (30%) -->
      <div class="pos-checkout-area">
        <div class="checkout-section fade-in">
          <div class="pos-card-header info">
            <h5>
              <i class="bi bi-credit-card"></i>
              Resumen de Compra
            </h5>
          </div>
          <div class="checkout-body">
            <!-- Customer Info -->
            <div style="margin-bottom: 1.5rem;">
              <h6 class="mb-3 fw-bold">Tipo de Documento</h6>
              
              <label class="payment-method-card">
                <input type="radio" name="document_type" value="boleta" checked>
                <div class="payment-method-content">
                  <i class="bi bi-receipt fs-4 text-info"></i>
                  <div>
                    <div class="fw-bold">Boleta</div>
                    <small class="text-muted">Sin datos tributarios</small>
                  </div>
                </div>
              </label>
              
              <label class="payment-method-card">
                <input type="radio" name="document_type" value="factura">
                <div class="payment-method-content">
                  <i class="bi bi-file-text fs-4 text-warning"></i>
                  <div>
                    <div class="fw-bold">Factura</div>
                    <small class="text-muted">Con datos tributarios</small>
                  </div>
                </div>
              </label>
            </div>

            <!-- Customer Data (Hidden by default, shown for factura) -->
            <div id="customer-data" style="display: none; margin-bottom: 1.5rem;">
              <h6 class="mb-3 fw-bold text-warning">Datos para Facturación</h6>
              
              <div class="mb-3">
                <label for="customer-rut" class="form-label fw-semibold">RUT Cliente *</label>
                <input type="text" 
                       id="customer-rut" 
                       class="pos-input" 
                       placeholder="12.345.678-9"
                       required>
              </div>
              
              <div class="mb-3">
                <label for="customer-razon-social" class="form-label fw-semibold">Razón Social *</label>
                <input type="text" 
                       id="customer-razon-social" 
                       class="pos-input" 
                       placeholder="Empresa S.A."
                       required>
              </div>
              
              <div class="mb-3">
                <label for="customer-rut-persona" class="form-label fw-semibold">RUT Persona de Contacto</label>
                <input type="text" 
                       id="customer-rut-persona" 
                       class="pos-input" 
                       placeholder="11.111.111-1">
              </div>
              
              <div class="mb-3">
                <label for="customer-nombre-persona" class="form-label fw-semibold">Nombre Persona de Contacto</label>
                <input type="text" 
                       id="customer-nombre-persona" 
                       class="pos-input" 
                       placeholder="Juan Pérez">
              </div>
            </div>
            
            <!-- Summary -->
            <div class="stats-card">
              <div class="stats-number" id="summary-items">0</div>
              <div class="stats-label">Artículos</div>
            </div>
            
            <div class="total-display">
              <div class="total-label">Total a Pagar</div>
              <div class="total-amount" id="summary-total">$0.00</div>
            </div>

            <!-- Payment Methods -->
            <div style="margin-bottom: 1.5rem;">
              <h6 class="mb-3 fw-bold">Método de Pago</h6>
              
              <label class="payment-method-card">
                <input type="radio" name="payment_method" value="efectivo" checked>
                <div class="payment-method-content">
                  <i class="bi bi-cash-coin fs-4 text-success"></i>
                  <div>
                    <div class="fw-bold">Efectivo</div>
                    <small class="text-muted">Pago en efectivo</small>
                  </div>
                </div>
              </label>
              
              <label class="payment-method-card">
                <input type="radio" name="payment_method" value="tarjeta">
                <div class="payment-method-content">
                  <i class="bi bi-credit-card fs-4 text-primary"></i>
                  <div>
                    <div class="fw-bold">Tarjeta</div>
                    <small class="text-muted">Débito o crédito</small>
                  </div>
                </div>
              </label>
              
              <label class="payment-method-card">
                <input type="radio" name="payment_method" value="transferencia">
                <div class="payment-method-content">
                  <i class="bi bi-bank fs-4 text-info"></i>
                  <div>
                    <div class="fw-bold">Transferencia</div>
                    <small class="text-muted">Transferencia bancaria</small>
                  </div>
                </div>
              </label>
            </div>

            <!-- Cash Payment Details -->
            <div id="cash-payment" style="margin-bottom: 1.5rem;">
              <h6 class="mb-3 fw-bold text-success">Pago en Efectivo</h6>
              <div style="margin-bottom: 1rem;">
                <label class="form-label fw-semibold">Monto Recibido</label>
                <input type="number" 
                       id="amount-received" 
                       class="pos-input" 
                       placeholder="0.00" 
                       step="0.01"
                       min="0">
              </div>
              
              <button type="button" 
                      id="quick-payment" 
                      class="pos-btn pos-btn-outline w-100 mb-3">
                <i class="bi bi-calculator"></i>
                Pago Exacto
              </button>
              
              <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <div class="fw-bold text-success">Cambio a Devolver</div>
                <div class="fs-4 fw-bold text-success" id="change-amount">$0.00</div>
              </div>
            </div>

            <!-- Card Payment Details -->
            <div id="card-payment" class="payment-detail">
              <div style="background: rgba(37, 99, 235, 0.1); border: 1px solid rgba(37, 99, 235, 0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <i class="bi bi-credit-card fs-3 text-primary mb-2 d-block"></i>
                <div class="fw-bold">Listo para cobrar con tarjeta</div>
                <small class="text-muted">Confirme el monto en el terminal</small>
              </div>
            </div>

            <!-- Transfer Payment Details -->
            <div id="transfer-payment" class="payment-detail">
              <div style="background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <i class="bi bi-bank fs-3 text-info mb-2 d-block"></i>
                <div class="fw-bold">Esperando transferencia</div>
                <small class="text-muted">Confirme la recepción del pago</small>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-grid gap-2">
              <button type="button" 
                      id="complete-sale" 
                      class="pos-btn pos-btn-success pos-btn-lg" 
                      disabled>
                <i class="bi bi-check-circle"></i>
                Completar Venta
              </button>
              
              <button type="button" 
                      id="new-sale" 
                      class="pos-btn pos-btn-outline">
                <i class="bi bi-plus-circle"></i>
                Nueva Venta
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="./assets/js/pos-app.js"></script>

</body>
</html>
