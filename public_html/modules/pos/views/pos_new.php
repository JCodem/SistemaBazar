<?php
// Verificar si el usuario está autenticado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /SistemaBazar/public_html/login.php');
    exit;
}

// Usar las variables de sesión user_nombre si están disponibles, sino usar usuario['nombre']
if (isset($_SESSION['user_nombre'])) {
    $nombre = htmlspecialchars($_SESSION['user_nombre']);
} elseif (isset($_SESSION['usuario']['nombre'])) {
    $nombre = htmlspecialchars($_SESSION['usuario']['nombre']);
} else {
    $nombre = 'Vendedor';
}
<?php // Vista parcial POS_NEW cargada vía layout_vendedor con middleware_unificado para autenticación y rol. ?>
      --primary-color: #2563eb;
      --secondary-color: #f1f5f9;
      --success-color: #10b981;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --dark-color: #1e293b;
      --light-color: #ffffff;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --shadow-light: 0 1px 3px rgba(0, 0, 0, 0.1);
      --shadow-medium: 0 4px 6px rgba(0, 0, 0, 0.1);
      --shadow-heavy: 0 10px 15px rgba(0, 0, 0, 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      background-color: var(--secondary-color);
      color: var(--text-primary);
      line-height: 1.6;
    }

    /* POS Components */
    .pos-card {
      background: var(--light-color);
      border-radius: 12px;
      box-shadow: var(--shadow-medium);
      border: 1px solid var(--border-color);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .pos-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-heavy);
    }

    .pos-card-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border-color);
      background: linear-gradient(135deg, var(--primary-color), #3b82f6);
      color: white;
      border-radius: 12px 12px 0 0;
    }

    .pos-card-header.info {
      background: linear-gradient(135deg, var(--success-color), #059669);
    }

    .pos-input {
      border: 2px solid var(--border-color);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      background: var(--light-color);
    }

    .pos-input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .pos-input-lg {
      padding: 1rem 1.25rem;
      font-size: 1.1rem;
      font-weight: 500;
    }

    .pos-btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
    }

    .pos-btn-primary {
      background: var(--primary-color);
      color: white;
    }

    .pos-btn-primary:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
    }

    .pos-btn-success {
      background: var(--success-color);
      color: white;
    }

    .pos-btn-success:hover {
      background: #059669;
      transform: translateY(-1px);
    }

    .pos-btn-outline {
      background: transparent;
      border: 2px solid var(--border-color);
      color: var(--text-primary);
    }

    .pos-btn-outline:hover {
      background: var(--secondary-color);
      border-color: var(--primary-color);
      color: var(--primary-color);
    }

    .pos-btn-lg {
      padding: 1rem 2rem;
      font-size: 1.1rem;
    }

    .pos-btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none !important;
    }

    .pos-table {
      background: var(--light-color);
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid var(--border-color);
    }

    .pos-table .table {
      margin: 0;
    }

    .pos-table .table th {
      background: var(--secondary-color);
      border-bottom: 2px solid var(--border-color);
      font-weight: 600;
      color: var(--text-primary);
      padding: 1rem;
    }

    .pos-table .table td {
      padding: 1rem;
      vertical-align: middle;
      border-bottom: 1px solid var(--border-color);
    }

    .pos-badge {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .pos-badge-primary {
      background: rgba(37, 99, 235, 0.1);
      color: var(--primary-color);
    }

    .pos-badge-success {
      background: rgba(16, 185, 129, 0.1);
      color: var(--success-color);
    }

    /* Search Dropdown */
    .search-dropdown {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      z-index: 1000;
      margin-top: 0.25rem;
    }

    .search-dropdown-content {
      background: var(--light-color);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      box-shadow: var(--shadow-heavy);
      max-height: 300px;
      overflow-y: auto;
    }

    .search-item {
      padding: 1rem;
      border-bottom: 1px solid var(--border-color);
      cursor: pointer;
      transition: background-color 0.2s ease;
    }

    .search-item:hover {
      background: var(--secondary-color);
    }

    .search-item:last-child {
      border-bottom: none;
    }

    /* Payment Methods */
    .payment-method-card {
      display: block;
      cursor: pointer;
    }

    .payment-method-card input[type="radio"] {
      display: none;
    }

    .payment-method-content {
      padding: 1rem;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      background: var(--light-color);
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.2s ease;
    }

    .payment-method-card:hover .payment-method-content {
      border-color: var(--primary-color);
      background: rgba(37, 99, 235, 0.02);
    }

    .payment-method-card input[type="radio"]:checked + .payment-method-content {
      border-color: var(--primary-color);
      background: rgba(37, 99, 235, 0.05);
    }

    .payment-detail {
      display: none;
    }

    /* Statistics */
    .stats-card {
      text-align: center;
      padding: 1.5rem;
      background: var(--light-color);
      border-radius: 8px;
      border: 1px solid var(--border-color);
    }

    .stats-number {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }

    .stats-label {
      font-size: 0.9rem;
      color: var(--text-secondary);
      font-weight: 500;
    }

    /* Animations */
    .fade-in {
      animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Quantity Controls */
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
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .quantity-btn:hover {
      background: var(--secondary-color);
      border-color: var(--primary-color);
    }

    .quantity-input {
      width: 60px;
      text-align: center;
      border: 1px solid var(--border-color);
      border-radius: 4px;
      padding: 0.25rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .container-fluid {
        padding: 1rem;
      }
      
      .pos-card {
        margin-bottom: 1.5rem;
      }
      
      .pos-btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
      }
    }
  </style>
</head>

<body>
  <!-- Header -->
  <nav class="navbar navbar-expand-lg" style="background: var(--light-color); border-bottom: 1px solid var(--border-color); box-shadow: var(--shadow-light);">
    <div class="container-fluid">
      <div class="navbar-brand fw-bold text-primary">
        <i class="bi bi-shop"></i>
        Sistema Bazar - POS
      </div>
      
      <div class="navbar-nav ms-auto">
        <div class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i>
            <span><?php echo $nombre; ?></span>
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/SistemaBazar/public_html/vendedor/dashboard.php">
              <i class="bi bi-house-door"></i> Dashboard
            </a></li>
            <li><a class="dropdown-item" href="/SistemaBazar/public_html/vendedor/inventario.php">
              <i class="bi bi-box"></i> Inventario
            </a></li>
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
    <div class="row g-4">
      <!-- Panel principal: Búsqueda y Carrito -->
      <div class="col-lg-8">
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
                
                <!-- Resultados de búsqueda desplegable -->
                <div id="search-dropdown" class="search-dropdown" style="display: none;">
                  <div class="search-dropdown-content">
                    <div id="search-results-list"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-2">
                <button type="button" 
                        id="clear-cart" 
                        class="pos-btn pos-btn-outline pos-btn-lg w-100">
                  <i class="bi bi-trash"></i>
                </button>
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
                  <span id="cart-count">0</span> artículos - <span id="cart-total">$0.00</span>
                </div>
              </div>
              
              <div class="pos-table">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th class="text-center">Cantidad</th>
                      <th class="text-end">Precio</th>
                      <th class="text-end">Total</th>
                      <th class="text-center">Acciones</th>
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
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Panel derecho: Finalizar Compra -->
      <div class="col-lg-4">
        <!-- Panel de Finalización de Compra -->
        <div class="pos-card fade-in mb-4">
          <div class="pos-card-header info">
            <h5 class="mb-0">
              <i class="bi bi-credit-card"></i>
              Finalizar Compra
            </h5>
          </div>
          <div class="card-body p-4">
            <!-- RUT del Cliente -->
            <div class="mb-4">
              <label for="customer-rut" class="form-label fw-semibold">RUT del Cliente (Opcional)</label>
              <input type="text" 
                     id="customer-rut" 
                     class="pos-input w-100" 
                     placeholder="12.345.678-9">
            </div>
            
            <!-- Resumen de totales -->
            <div class="mb-4">
              <div class="row g-3 mb-3">
                <div class="col-12">
                  <div class="stats-card">
                    <div class="stats-number text-primary" id="summary-items">0</div>
                    <div class="stats-label">Artículos</div>
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
                  <input type="radio" name="payment_method" value="efectivo" checked>
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
              
              <div class="mb-3">
                <button type="button" 
                        id="quick-payment" 
                        class="pos-btn pos-btn-outline w-100">
                  <i class="bi bi-calculator"></i>
                  Pago Exacto
                </button>
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
                    <div class="fw-bold">Listo para cobrar con tarjeta</div>
                    <small class="text-muted">Confirme el monto en el terminal</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Panel de Transferencia -->
            <div id="transfer-payment" class="payment-detail mb-4" style="display: none;">
              <div class="p-3 rounded-3" style="background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.2);">
                <div class="d-flex align-items-center gap-3">
                  <i class="bi bi-bank fs-3 text-info"></i>
                  <div>
                    <div class="fw-bold">Esperando transferencia</div>
                    <small class="text-muted">Confirme la recepción del pago</small>
                  </div>
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
  
  <script>
    // Variables globales
    let cart = [];
    let searchTimeout;
    
    // Función de depuración global
    function debugPOS(message, data = null) {
      console.log('[POS DEBUG]', message, data || '');
    }
    
    // Función de prueba para añadir producto
    function testAddProduct() {
      debugPOS('Probando añadir producto de prueba...');
      const testProduct = {
        id: 999,
        nombre: 'Producto de Prueba',
        precio: 1500,
        stock: 10,
        sku: 'TEST001',
        codigo_barras: '123456789'
      };
      addToCart(testProduct);
    }

    // Inicialización del DOM
    document.addEventListener('DOMContentLoaded', function() {
      debugPOS('DOM cargado - Inicializando POS...');
      
      // Verificar elementos críticos
      const searchInput = document.getElementById('product-search');
      const cartItems = document.getElementById('cart-items');
      const searchDropdown = document.getElementById('search-dropdown');
      
      debugPOS('Elementos encontrados:', {
        searchInput: !!searchInput,
        cartItems: !!cartItems,
        searchDropdown: !!searchDropdown
      });
      
      if (!searchInput || !cartItems || !searchDropdown) {
        debugPOS('ERROR: Elementos críticos no encontrados');
        return;
      }
      
      initializeEventListeners();
      updateCartDisplay();
      
      // Función de prueba disponible en consola
      window.testAddProduct = testAddProduct;
      window.debugPOS = debugPOS;
      
      debugPOS('POS inicializado correctamente');
    });

    function initializeEventListeners() {
      debugPOS('Configurando event listeners...');
      
      // Campo de búsqueda
      const searchInput = document.getElementById('product-search');
      if (searchInput) {
        searchInput.addEventListener('input', handleSmartSearch);
        searchInput.addEventListener('keydown', function(e) {
          if (e.key === 'Enter') {
            e.preventDefault();
            handleSmartSearch();
          }
        });
        debugPOS('Event listener para búsqueda configurado');
      }

      // Botón limpiar carrito
      const clearCartBtn = document.getElementById('clear-cart');
      if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
        debugPOS('Event listener para limpiar carrito configurado');
      }

      // Botón pago exacto
      const quickPaymentBtn = document.getElementById('quick-payment');
      if (quickPaymentBtn) {
        quickPaymentBtn.addEventListener('click', setExactPayment);
        debugPOS('Event listener para pago exacto configurado');
      }

      // Campo monto recibido
      const amountReceived = document.getElementById('amount-received');
      if (amountReceived) {
        amountReceived.addEventListener('input', calculateChange);
        debugPOS('Event listener para cálculo de cambio configurado');
      }

      // Métodos de pago
      const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
      paymentMethods.forEach(radio => {
        radio.addEventListener('change', handlePaymentMethodChange);
      });
      debugPOS('Event listeners para métodos de pago configurados');

      // Botón completar venta
      const completeSaleBtn = document.getElementById('complete-sale');
      if (completeSaleBtn) {
        completeSaleBtn.addEventListener('click', completeSale);
        debugPOS('Event listener para completar venta configurado');
      }

      // Botón nueva venta
      const newSaleBtn = document.getElementById('new-sale');
      if (newSaleBtn) {
        newSaleBtn.addEventListener('click', newSale);
        debugPOS('Event listener para nueva venta configurado');
      }

      // Cerrar dropdown al hacer clic fuera
      document.addEventListener('click', function(e) {
        const searchInput = document.getElementById('product-search');
        const searchDropdown = document.getElementById('search-dropdown');
        
        if (searchInput && searchDropdown && 
            !searchInput.contains(e.target) && 
            !searchDropdown.contains(e.target)) {
          searchDropdown.style.display = 'none';
        }
      });
    }

    // Función de búsqueda inteligente
    function handleSmartSearch() {
      const searchInput = document.getElementById('product-search');
      const query = searchInput.value.trim();
      
      debugPOS('Búsqueda iniciada:', query);
      
      // Limpiar timeout anterior
      if (searchTimeout) {
        clearTimeout(searchTimeout);
      }
      
      if (query.length < 2) {
        hideSearchDropdown();
        return;
      }
      
      // Debounce de 300ms
      searchTimeout = setTimeout(() => {
        performSearch(query);
      }, 300);
    }

    function performSearch(query) {
      debugPOS('Realizando búsqueda en servidor:', query);
      
      const formData = new FormData();
      formData.append('action', 'search');
      formData.append('query', query);
      
      fetch('/SistemaBazar/modules/pos/ajax_handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        debugPOS('Respuesta recibida del servidor');
        return response.json();
      })
      .then(data => {
        debugPOS('Datos de búsqueda:', data);
        
        if (data.success && data.productos) {
          displaySearchDropdown(data.productos);
        } else {
          debugPOS('No se encontraron productos o error:', data.message);
          displayNoResults();
        }
      })
      .catch(error => {
        debugPOS('Error en búsqueda:', error);
        displaySearchError();
      });
    }

    function displaySearchDropdown(productos) {
      debugPOS('Mostrando resultados de búsqueda:', productos.length);
      
      const searchDropdown = document.getElementById('search-dropdown');
      const resultsList = document.getElementById('search-results-list');
      
      if (!searchDropdown || !resultsList) {
        debugPOS('ERROR: Elementos de dropdown no encontrados');
        return;
      }
      
      // Limpiar resultados anteriores
      resultsList.innerHTML = '';
      
      if (productos.length === 0) {
        displayNoResults();
        return;
      }
      
      productos.forEach(producto => {
        const item = document.createElement('div');
        item.className = 'search-item';
        
        const name = document.createElement('div');
        name.className = 'fw-semibold';
        name.textContent = producto.nombre;
        
        const details = document.createElement('div');
        details.className = 'small text-muted';
        details.textContent = `SKU: ${producto.sku || 'N/A'} | Precio: $${parseFloat(producto.precio).toLocaleString()} | Stock: ${producto.stock}`;
        
        item.appendChild(name);
        item.appendChild(details);
        
        // Event listener para seleccionar producto
        item.addEventListener('click', () => {
          debugPOS('Producto seleccionado:', producto);
          addToCart(producto);
          hideSearchDropdown();
          clearSearchInput();
        });
        
        resultsList.appendChild(item);
      });
      
      searchDropdown.style.display = 'block';
      debugPOS('Dropdown mostrado con', productos.length, 'productos');
    }

    function displayNoResults() {
      const searchDropdown = document.getElementById('search-dropdown');
      const resultsList = document.getElementById('search-results-list');
      
      if (!searchDropdown || !resultsList) return;
      
      resultsList.innerHTML = '';
      
      const noResults = document.createElement('div');
      noResults.className = 'search-item text-center text-muted';
      noResults.innerHTML = '<i class="bi bi-search"></i><br>No se encontraron productos';
      
      resultsList.appendChild(noResults);
      searchDropdown.style.display = 'block';
      
      debugPOS('No se encontraron resultados');
    }

    function displaySearchError() {
      const searchDropdown = document.getElementById('search-dropdown');
      const resultsList = document.getElementById('search-results-list');
      
      if (!searchDropdown || !resultsList) return;
      
      resultsList.innerHTML = '';
      
      const errorDiv = document.createElement('div');
      errorDiv.className = 'search-item text-center text-danger';
      errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i><br>Error en la búsqueda';
      
      resultsList.appendChild(errorDiv);
      searchDropdown.style.display = 'block';
      
      debugPOS('Error mostrado en dropdown');
    }

    function hideSearchDropdown() {
      const searchDropdown = document.getElementById('search-dropdown');
      if (searchDropdown) {
        searchDropdown.style.display = 'none';
      }
    }

    function clearSearchInput() {
      const searchInput = document.getElementById('product-search');
      if (searchInput) {
        searchInput.value = '';
      }
    }

    // Función para añadir al carrito
    function addToCart(producto) {
      debugPOS('Intentando añadir al carrito:', producto);
      
      if (!producto || !producto.id) {
        debugPOS('ERROR: Producto inválido');
        return;
      }
      
      // Verificar stock
      if (parseInt(producto.stock) <= 0) {
        debugPOS('ERROR: Producto sin stock');
        alert('Este producto no tiene stock disponible');
        return;
      }
      
      // Buscar si ya existe en el carrito
      const existingIndex = cart.findIndex(item => item.id == producto.id);
      
      if (existingIndex !== -1) {
        // Verificar si se puede añadir más cantidad
        if (cart[existingIndex].cantidad >= parseInt(producto.stock)) {
          debugPOS('ERROR: No hay más stock disponible');
          alert('No hay más stock disponible para este producto');
          return;
        }
        
        cart[existingIndex].cantidad += 1;
        debugPOS('Cantidad incrementada para producto existente:', cart[existingIndex]);
      } else {
        // Añadir nuevo producto
        const cartItem = {
          id: producto.id,
          nombre: producto.nombre,
          precio: parseFloat(producto.precio),
          cantidad: 1,
          stock: parseInt(producto.stock),
          sku: producto.sku || '',
          codigo_barras: producto.codigo_barras || ''
        };
        
        cart.push(cartItem);
        debugPOS('Nuevo producto añadido al carrito:', cartItem);
      }
      
      updateCartDisplay();
      debugPOS('Carrito actualizado. Total de productos:', cart.length);
    }

    // Función para actualizar la visualización del carrito
    function updateCartDisplay() {
      debugPOS('Actualizando visualización del carrito...');
      
      const cartItems = document.getElementById('cart-items');
      const cartCount = document.getElementById('cart-count');
      const cartTotal = document.getElementById('cart-total');
      const summaryItems = document.getElementById('summary-items');
      const summaryTotal = document.getElementById('summary-total');
      const completeSaleBtn = document.getElementById('complete-sale');
      
      if (!cartItems) {
        debugPOS('ERROR: Elemento cart-items no encontrado');
        return;
      }
      
      // Limpiar contenido actual
      cartItems.innerHTML = '';
      
      if (cart.length === 0) {
        // Mostrar mensaje de carrito vacío
        const emptyRow = document.createElement('tr');
        emptyRow.id = 'empty-cart-row';
        emptyRow.innerHTML = `
          <td colspan="5" class="text-center py-5 text-muted">
            <i class="bi bi-cart-x fs-1 d-block mb-2 opacity-50"></i>
            <div>El carrito está vacío</div>
            <small>Busca productos para añadir al carrito</small>
          </td>
        `;
        cartItems.appendChild(emptyRow);
        
        // Actualizar contadores
        if (cartCount) cartCount.textContent = '0';
        if (cartTotal) cartTotal.textContent = '$0.00';
        if (summaryItems) summaryItems.textContent = '0';
        if (summaryTotal) summaryTotal.textContent = '$0.00';
        if (completeSaleBtn) completeSaleBtn.disabled = true;
        
        debugPOS('Carrito vacío mostrado');
        return;
      }
      
      let totalAmount = 0;
      let totalItems = 0;
      
      cart.forEach((item, index) => {
        const itemTotal = item.precio * item.cantidad;
        totalAmount += itemTotal;
        totalItems += item.cantidad;
        
        const row = document.createElement('tr');
        
        // Columna del producto
        const productCell = document.createElement('td');
        const productName = document.createElement('div');
        productName.className = 'fw-semibold';
        productName.textContent = item.nombre;
        
        const productDetails = document.createElement('div');
        productDetails.className = 'small text-muted';
        productDetails.textContent = `SKU: ${item.sku}`;
        
        productCell.appendChild(productName);
        productCell.appendChild(productDetails);
        
        // Columna de cantidad
        const quantityCell = document.createElement('td');
        quantityCell.className = 'text-center';
        
        const quantityControls = document.createElement('div');
        quantityControls.className = 'quantity-controls justify-content-center';
        
        const decreaseBtn = document.createElement('button');
        decreaseBtn.className = 'quantity-btn';
        decreaseBtn.innerHTML = '<i class="bi bi-dash"></i>';
        decreaseBtn.addEventListener('click', () => {
          decreaseQuantity(index);
        });
        
        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.className = 'quantity-input';
        quantityInput.value = item.cantidad;
        quantityInput.min = '1';
        quantityInput.max = item.stock;
        quantityInput.addEventListener('change', (e) => {
          updateQuantity(index, parseInt(e.target.value));
        });
        
        const increaseBtn = document.createElement('button');
        increaseBtn.className = 'quantity-btn';
        increaseBtn.innerHTML = '<i class="bi bi-plus"></i>';
        increaseBtn.addEventListener('click', () => {
          increaseQuantity(index);
        });
        
        quantityControls.appendChild(decreaseBtn);
        quantityControls.appendChild(quantityInput);
        quantityControls.appendChild(increaseBtn);
        quantityCell.appendChild(quantityControls);
        
        // Columna de precio
        const priceCell = document.createElement('td');
        priceCell.className = 'text-end';
        priceCell.textContent = `$${item.precio.toLocaleString()}`;
        
        // Columna de total
        const totalCell = document.createElement('td');
        totalCell.className = 'text-end fw-semibold';
        totalCell.textContent = `$${itemTotal.toLocaleString()}`;
        
        // Columna de acciones
        const actionsCell = document.createElement('td');
        actionsCell.className = 'text-center';
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-sm btn-outline-danger';
        removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
        removeBtn.addEventListener('click', () => {
          removeFromCart(index);
        });
        
        actionsCell.appendChild(removeBtn);
        
        // Añadir todas las columnas a la fila
        row.appendChild(productCell);
        row.appendChild(quantityCell);
        row.appendChild(priceCell);
        row.appendChild(totalCell);
        row.appendChild(actionsCell);
        
        cartItems.appendChild(row);
      });
      
      // Actualizar contadores
      if (cartCount) cartCount.textContent = totalItems.toString();
      if (cartTotal) cartTotal.textContent = `$${totalAmount.toLocaleString()}`;
      if (summaryItems) summaryItems.textContent = totalItems.toString();
      if (summaryTotal) summaryTotal.textContent = `$${totalAmount.toLocaleString()}`;
      if (completeSaleBtn) completeSaleBtn.disabled = cart.length === 0;
      
      debugPOS('Carrito actualizado:', {
        items: cart.length,
        totalItems: totalItems,
        totalAmount: totalAmount
      });
    }

    function increaseQuantity(index) {
      if (cart[index].cantidad < cart[index].stock) {
        cart[index].cantidad += 1;
        updateCartDisplay();
        debugPOS('Cantidad incrementada:', cart[index]);
      } else {
        alert('No hay más stock disponible');
      }
    }

    function decreaseQuantity(index) {
      if (cart[index].cantidad > 1) {
        cart[index].cantidad -= 1;
        updateCartDisplay();
        debugPOS('Cantidad decrementada:', cart[index]);
      } else {
        removeFromCart(index);
      }
    }

    function updateQuantity(index, newQuantity) {
      if (newQuantity < 1 || newQuantity > cart[index].stock) {
        return;
      }
      cart[index].cantidad = newQuantity;
      updateCartDisplay();
      debugPOS('Cantidad actualizada:', cart[index]);
    }

    function removeFromCart(index) {
      const removedItem = cart.splice(index, 1)[0];
      updateCartDisplay();
      debugPOS('Producto removido del carrito:', removedItem);
    }

    function clearCart() {
      if (cart.length > 0 && confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
        cart = [];
        updateCartDisplay();
        clearSearchInput();
        debugPOS('Carrito limpiado');
      }
    }

    function handlePaymentMethodChange(e) {
      const method = e.target.value;
      debugPOS('Método de pago cambiado:', method);
      
      // Ocultar todos los paneles de pago
      document.getElementById('cash-payment').style.display = 'none';
      document.getElementById('card-payment').style.display = 'none';
      document.getElementById('transfer-payment').style.display = 'none';
      
      // Mostrar el panel correspondiente
      if (method === 'efectivo') {
        document.getElementById('cash-payment').style.display = 'block';
      } else if (method === 'tarjeta') {
        document.getElementById('card-payment').style.display = 'block';
      } else if (method === 'transferencia') {
        document.getElementById('transfer-payment').style.display = 'block';
      }
      
      calculateChange();
    }

    function setExactPayment() {
      const total = getTotalAmount();
      const amountReceived = document.getElementById('amount-received');
      if (amountReceived) {
        amountReceived.value = total.toFixed(2);
        calculateChange();
        debugPOS('Pago exacto establecido:', total);
      }
    }

    function calculateChange() {
      const total = getTotalAmount();
      const amountReceived = parseFloat(document.getElementById('amount-received').value) || 0;
      const change = amountReceived - total;
      
      const changeAmount = document.getElementById('change-amount');
      if (changeAmount) {
        if (change >= 0) {
          changeAmount.textContent = `$${change.toLocaleString()}`;
          changeAmount.className = 'fs-4 fw-bold text-success';
        } else {
          changeAmount.textContent = `$${Math.abs(change).toLocaleString()}`;
          changeAmount.className = 'fs-4 fw-bold text-danger';
        }
      }
      
      debugPOS('Cambio calculado:', {
        total: total,
        received: amountReceived,
        change: change
      });
    }

    function getTotalAmount() {
      return cart.reduce((total, item) => total + (item.precio * item.cantidad), 0);
    }

    function completeSale() {
      if (cart.length === 0) {
        alert('El carrito está vacío');
        return;
      }
      
      const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
      const customerRut = document.getElementById('customer-rut').value;
      const total = getTotalAmount();
      
      debugPOS('Completando venta:', {
        items: cart.length,
        total: total,
        paymentMethod: paymentMethod,
        customerRut: customerRut
      });
      
      // Validar pago en efectivo
      if (paymentMethod === 'efectivo') {
        const amountReceived = parseFloat(document.getElementById('amount-received').value) || 0;
        if (amountReceived < total) {
          alert('El monto recibido es insuficiente');
          return;
        }
      }
      
      // Preparar datos para enviar
      const saleData = {
        action: 'complete_sale',
        items: cart,
        payment_method: paymentMethod,
        customer_rut: customerRut,
        total: total
      };
      
      if (paymentMethod === 'efectivo') {
        saleData.amount_received = parseFloat(document.getElementById('amount-received').value);
        saleData.change_amount = saleData.amount_received - total;
      }
      
      // Enviar al servidor
      fetch('/SistemaBazar/modules/pos/ajax_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
      })
      .then(response => response.json())
      .then(data => {
        debugPOS('Respuesta de venta:', data);
        
        if (data.success) {
          alert('Venta completada exitosamente');
          newSale();
        } else {
          alert('Error al completar la venta: ' + (data.message || 'Error desconocido'));
        }
      })
      .catch(error => {
        debugPOS('Error al completar venta:', error);
        alert('Error al procesar la venta');
      });
    }

    function newSale() {
      cart = [];
      updateCartDisplay();
      clearSearchInput();
      
      // Limpiar campos de pago
      document.getElementById('customer-rut').value = '';
      document.getElementById('amount-received').value = '';
      document.querySelector('input[name="payment_method"][value="efectivo"]').checked = true;
      
      // Mostrar panel de efectivo
      document.getElementById('cash-payment').style.display = 'block';
      document.getElementById('card-payment').style.display = 'none';
      document.getElementById('transfer-payment').style.display = 'none';
      
      calculateChange();
      
      // Enfocar campo de búsqueda
      document.getElementById('product-search').focus();
      
      debugPOS('Nueva venta iniciada');
    }
  </script>
</body>
</html>
