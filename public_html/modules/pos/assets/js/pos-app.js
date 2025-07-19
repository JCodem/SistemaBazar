// POS JavaScript - Consolidated file for the Point of Sale system

console.log('🚀 POS JavaScript cargado correctamente');

// Global variables
let cart = [];
let searchTimeout = null;

// Debug function
function debugPOS(message, data = null) {
  console.log('[POS DEBUG]', message, data || '');
}

// Initialize POS when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
  debugPOS('Inicializando sistema POS...');
  
  // Verify critical elements exist
  const searchInput = document.getElementById('product-search');
  const cartItems = document.getElementById('cart-items');
  const searchDropdown = document.getElementById('search-dropdown');
  
  if (!searchInput || !cartItems || !searchDropdown) {
    debugPOS('ERROR: Elementos críticos no encontrados');
    return;
  }
  
  // Initialize event listeners
  initializeEventListeners();
  
  // Initialize cart display
  updateCartDisplay();
  
  // Make functions available globally for testing
  window.debugPOS = debugPOS;
  window.addToCart = addToCart;
  window.cart = cart;
  
  debugPOS('POS inicializado correctamente');
});

// Initialize all event listeners
function initializeEventListeners() {
  debugPOS('Configurando event listeners...');
  
  // Search input
  const searchInput = document.getElementById('product-search');
  if (searchInput) {
    searchInput.addEventListener('input', handleSmartSearch);
    debugPOS('✅ Search input listener configurado');
  }
  
  // Clear cart button
  const clearCartBtn = document.getElementById('clear-cart');
  if (clearCartBtn) {
    clearCartBtn.addEventListener('click', clearCart);
    debugPOS('✅ Clear cart button listener configurado');
  }
  
  // Quick payment button
  const quickPaymentBtn = document.getElementById('quick-payment');
  if (quickPaymentBtn) {
    quickPaymentBtn.addEventListener('click', setExactPayment);
    debugPOS('✅ Quick payment button listener configurado');
  }
  
  // Amount received input
  const amountReceived = document.getElementById('amount-received');
  if (amountReceived) {
    amountReceived.addEventListener('input', calculateChange);
    debugPOS('✅ Amount received input listener configurado');
  }
  
    // Document type radios
  const documentTypes = document.querySelectorAll('input[name="document_type"]');
  documentTypes.forEach(radio => {
    radio.addEventListener('change', handleDocumentTypeChange);
  });
  debugPOS('Event listeners para tipos de documento configurados');

  // Payment method radios
  const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
  paymentMethods.forEach(radio => {
    radio.addEventListener('change', handlePaymentMethodChange);
  });
  debugPOS('✅ Payment method listeners configurados');
  
  // Complete sale button
  const completeSaleBtn = document.getElementById('complete-sale');
  if (completeSaleBtn) {
    completeSaleBtn.addEventListener('click', completeSale);
    debugPOS('✅ Complete sale button listener configurado');
  }
  
  // New sale button
  const newSaleBtn = document.getElementById('new-sale');
  if (newSaleBtn) {
    newSaleBtn.addEventListener('click', newSale);
    debugPOS('✅ New sale button listener configurado');
  }
  
  // Click outside to close dropdown
  document.addEventListener('click', function(e) {
    const searchInput = document.getElementById('product-search');
    const searchDropdown = document.getElementById('search-dropdown');
    
    if (searchInput && searchDropdown && 
        !searchInput.contains(e.target) && 
        !searchDropdown.contains(e.target)) {
      hideSearchDropdown();
    }
  });
  
  debugPOS('✅ Todos los event listeners configurados');
}

// Search functionality
function handleSmartSearch() {
  const searchInput = document.getElementById('product-search');
  const query = searchInput.value.trim();
  
  debugPOS('Búsqueda iniciada:', query);
  
  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  
  if (query.length < 2) {
    hideSearchDropdown();
    return;
  }
  
  // Debounce search
  searchTimeout = setTimeout(() => {
    performSearch(query);
  }, 300);
}

function performSearch(query) {
  debugPOS('Realizando búsqueda:', query);
  
  const formData = new FormData();
  formData.append('action', 'search');
  formData.append('query', query);
  
  fetch('./ajax_handler.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    debugPOS('Response status:', response.status);
    debugPOS('Response headers:', response.headers);
    
    // Check if response is ok
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    // Check content type
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      debugPOS('WARNING: Response is not JSON, content-type:', contentType);
      // Get the text to see what we actually received
      return response.text().then(text => {
        debugPOS('Response text:', text);
        throw new Error('Response is not JSON: ' + text.substring(0, 200));
      });
    }
    
    return response.json();
  })
  .then(data => {
    debugPOS('Resultados de búsqueda:', data);
    
    if (data.success && data.productos) {
      displaySearchDropdown(data.productos);
    } else {
      displayNoResults();
    }
  })
  .catch(error => {
    debugPOS('Error en búsqueda:', error);
    displaySearchError();
  });
}

function displaySearchDropdown(productos) {
  const searchDropdown = document.getElementById('search-dropdown');
  const resultsList = document.getElementById('search-results-list');
  
  if (!searchDropdown || !resultsList) {
    debugPOS('ERROR: Elementos de dropdown no encontrados');
    return;
  }
  
  resultsList.innerHTML = '';
  
  if (productos.length === 0) {
    displayNoResults();
    return;
  }
  
  productos.forEach(producto => {
    const item = document.createElement('div');
    item.className = 'search-item';
    
    const name = document.createElement('div');
    name.className = 'search-item-name';
    name.textContent = producto.nombre;
    
    const details = document.createElement('div');
    details.className = 'search-item-details';
    details.textContent = `SKU: ${producto.sku || 'N/A'} | Precio: $${parseFloat(producto.precio).toLocaleString()} | Stock: ${producto.stock}`;
    
    item.appendChild(name);
    item.appendChild(details);
    
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
  noResults.className = 'search-item';
  noResults.style.textAlign = 'center';
  noResults.innerHTML = '<i class="bi bi-search"></i><br><strong>No se encontraron productos</strong>';
  
  resultsList.appendChild(noResults);
  searchDropdown.style.display = 'block';
}

function displaySearchError() {
  const searchDropdown = document.getElementById('search-dropdown');
  const resultsList = document.getElementById('search-results-list');
  
  if (!searchDropdown || !resultsList) return;
  
  resultsList.innerHTML = '';
  
  const errorDiv = document.createElement('div');
  errorDiv.className = 'search-item';
  errorDiv.style.textAlign = 'center';
  errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i><br><strong>Error en la búsqueda</strong>';
  
  resultsList.appendChild(errorDiv);
  searchDropdown.style.display = 'block';
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

// Cart functionality
function addToCart(producto) {
  debugPOS('Añadiendo al carrito:', producto);
  
  if (!producto || !producto.id) {
    debugPOS('ERROR: Producto inválido');
    return;
  }
  
  if (parseInt(producto.stock) <= 0) {
    alert('Este producto no tiene stock disponible');
    return;
  }
  
  const existingIndex = cart.findIndex(item => item.id == producto.id);
  
  if (existingIndex !== -1) {
    if (cart[existingIndex].cantidad >= parseInt(producto.stock)) {
      alert('No hay más stock disponible para este producto');
      return;
    }
    cart[existingIndex].cantidad += 1;
  } else {
    cart.push({
      id: producto.id,
      nombre: producto.nombre,
      precio: parseFloat(producto.precio),
      cantidad: 1,
      stock: parseInt(producto.stock),
      sku: producto.sku || ''
    });
  }
  
  updateCartDisplay();
  debugPOS('Producto añadido. Cart length:', cart.length);
}

function updateCartDisplay() {
  debugPOS('Actualizando display del carrito...');
  
  const cartItems = document.getElementById('cart-items');
  const cartCount = document.getElementById('cart-count');
  const cartTotal = document.getElementById('cart-total');
  const summaryItems = document.getElementById('summary-items');
  const summaryTotal = document.getElementById('summary-total');
  const completeSaleBtn = document.getElementById('complete-sale');
  
  if (!cartItems) {
    debugPOS('ERROR: cart-items element not found');
    return;
  }
  
  cartItems.innerHTML = '';
  
  if (cart.length === 0) {
    const emptyRow = document.createElement('tr');
    emptyRow.innerHTML = `
      <td colspan="5">
        <div class="empty-cart">
          <i class="bi bi-cart-x"></i>
          <div><strong>El carrito está vacío</strong></div>
          <small>Busca productos para añadir al carrito</small>
        </div>
      </td>
    `;
    cartItems.appendChild(emptyRow);
    
    // Update counters
    if (cartCount) cartCount.textContent = '0';
    if (cartTotal) cartTotal.textContent = '$0.00';
    if (summaryItems) summaryItems.textContent = '0';
    if (summaryTotal) summaryTotal.textContent = '$0.00';
    if (completeSaleBtn) completeSaleBtn.disabled = true;
    
    return;
  }
  
  let totalAmount = 0;
  let totalItems = 0;
  
  cart.forEach((item, index) => {
    const itemTotal = item.precio * item.cantidad;
    totalAmount += itemTotal;
    totalItems += item.cantidad;
    
    const row = document.createElement('tr');
    
    // Product cell
    const productCell = document.createElement('td');
    productCell.innerHTML = `
      <div class="fw-semibold">${item.nombre}</div>
      <div class="small text-muted">SKU: ${item.sku}</div>
    `;
    
    // Quantity cell
    const quantityCell = document.createElement('td');
    quantityCell.className = 'text-center';
    quantityCell.innerHTML = `
      <div class="quantity-controls">
        <button class="quantity-btn" onclick="decreaseQuantity(${index})">
          <i class="bi bi-dash"></i>
        </button>
        <input type="number" class="quantity-input" value="${item.cantidad}" 
               min="1" max="${item.stock}" onchange="updateQuantity(${index}, this.value)">
        <button class="quantity-btn" onclick="increaseQuantity(${index})">
          <i class="bi bi-plus"></i>
        </button>
      </div>
    `;
    
    // Price cell
    const priceCell = document.createElement('td');
    priceCell.className = 'text-end';
    priceCell.textContent = `$${item.precio.toLocaleString()}`;
    
    // Total cell
    const totalCell = document.createElement('td');
    totalCell.className = 'text-end fw-semibold';
    totalCell.textContent = `$${itemTotal.toLocaleString()}`;
    
    // Actions cell
    const actionsCell = document.createElement('td');
    actionsCell.className = 'text-center';
    actionsCell.innerHTML = `
      <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
        <i class="bi bi-trash"></i>
      </button>
    `;
    
    row.appendChild(productCell);
    row.appendChild(quantityCell);
    row.appendChild(priceCell);
    row.appendChild(totalCell);
    row.appendChild(actionsCell);
    
    cartItems.appendChild(row);
  });
  
  // Update counters
  if (cartCount) cartCount.textContent = totalItems.toString();
  if (cartTotal) cartTotal.textContent = `$${totalAmount.toLocaleString()}`;
  if (summaryItems) summaryItems.textContent = totalItems.toString();
  if (summaryTotal) summaryTotal.textContent = `$${totalAmount.toLocaleString()}`;
  if (completeSaleBtn) completeSaleBtn.disabled = cart.length === 0;
  
  debugPOS('Cart actualizado:', {items: cart.length, total: totalAmount});
}

// Quantity management
function increaseQuantity(index) {
  if (cart[index].cantidad < cart[index].stock) {
    cart[index].cantidad += 1;
    updateCartDisplay();
  } else {
    alert('No hay más stock disponible');
  }
}

function decreaseQuantity(index) {
  if (cart[index].cantidad > 1) {
    cart[index].cantidad -= 1;
    updateCartDisplay();
  } else {
    removeFromCart(index);
  }
}

function updateQuantity(index, newQuantity) {
  const qty = parseInt(newQuantity);
  if (qty < 1 || qty > cart[index].stock) {
    updateCartDisplay(); // Reset to valid value
    return;
  }
  cart[index].cantidad = qty;
  updateCartDisplay();
}

function removeFromCart(index) {
  cart.splice(index, 1);
  updateCartDisplay();
}

function clearCart() {
  if (cart.length > 0 && confirm('¿Está seguro de vaciar el carrito?')) {
    cart = [];
    updateCartDisplay();
    clearSearchInput();
  }
}

// Document type functionality
function handleDocumentTypeChange(e) {
  const documentType = e.target.value;
  const customerData = document.getElementById('customer-data');
  
  debugPOS('Tipo de documento cambiado a:', documentType);
  
  if (documentType === 'factura') {
    customerData.style.display = 'block';
    // Make required fields mandatory
    document.getElementById('customer-rut').setAttribute('required', 'required');
    document.getElementById('customer-razon-social').setAttribute('required', 'required');
    document.getElementById('customer-direccion').setAttribute('required', 'required');
  } else {
    customerData.style.display = 'none';
    // Remove required attributes
    document.getElementById('customer-rut').removeAttribute('required');
    document.getElementById('customer-razon-social').removeAttribute('required');
    document.getElementById('customer-direccion').removeAttribute('required');
    
    // Clear values
    document.getElementById('customer-rut').value = '';
    document.getElementById('customer-razon-social').value = '';
    document.getElementById('customer-direccion').value = '';
    document.getElementById('customer-rut-persona').value = '';
    document.getElementById('customer-nombre-persona').value = '';
  }
}

// Payment functionality
function handlePaymentMethodChange(e) {
  const method = e.target.value;
  debugPOS('Método de pago cambiado:', method);
  
  // Hide all payment details
  document.getElementById('cash-payment').style.display = 'none';
  document.getElementById('card-payment').style.display = 'none';
  document.getElementById('transfer-payment').style.display = 'none';
  
  // Show selected payment method details
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
  const documentType = document.querySelector('input[name="document_type"]:checked').value;
  const total = getTotalAmount();
  
  // Validación estricta para factura en frontend
  if (documentType === 'factura') {
    const customerRut = document.getElementById('customer-rut').value.trim();
    const customerRazonSocial = document.getElementById('customer-razon-social').value.trim();
    const customerDireccion = document.getElementById('customer-direccion').value.trim();
    debugPOS('Validando datos de cliente para factura:', {customerRut, customerRazonSocial, customerDireccion});
    if (!customerRut || !customerRazonSocial || !customerDireccion) {
      debugPOS('ERROR: Datos de cliente incompletos para factura');
      alert('Para factura, el RUT, Razón Social y Dirección son obligatorios');
      return;
    }
  } else {
    debugPOS('Tipo de documento es boleta: se permite venta sin datos de cliente');
  }
  
  // Validate cash payment
  if (paymentMethod === 'efectivo') {
    const amountReceived = parseFloat(document.getElementById('amount-received').value) || 0;
    if (amountReceived < total) {
      alert('El monto recibido es insuficiente');
      return;
    }
  }
  
  debugPOS('Completando venta:', {items: cart.length, total, method: paymentMethod, documentType});
  
  // Prepare sale data
  const saleData = {
    action: 'complete_sale',
    items: cart,
    payment_method: paymentMethod,
    document_type: documentType,
    total: total
  };
  
  // Add customer data based on document type
  if (documentType === 'factura') {
    saleData.customer_rut = document.getElementById('customer-rut').value.trim();
    saleData.customer_razon_social = document.getElementById('customer-razon-social').value.trim();
    saleData.customer_direccion = document.getElementById('customer-direccion').value.trim();
    saleData.customer_rut_persona = document.getElementById('customer-rut-persona').value.trim();
    saleData.customer_nombre_persona = document.getElementById('customer-nombre-persona').value.trim();
  } else {
    // For boleta, only optional RUT (moved from customer_rut to general rut field)
    saleData.customer_rut = '';
    saleData.customer_razon_social = '';
    saleData.customer_direccion = '';
    saleData.customer_rut_persona = '';
    saleData.customer_nombre_persona = '';
  }
  
  if (paymentMethod === 'efectivo') {
    saleData.amount_received = parseFloat(document.getElementById('amount-received').value);
    saleData.change_amount = saleData.amount_received - total;
  }
  
  // Send to server
  debugPOS('Enviando datos de venta al backend:', saleData);
  fetch('./ajax_handler.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(saleData)
  })
  .then(response => {
    debugPOS('Respuesta HTTP recibida:', response.status, response);
    return response.json().catch(err => {
      debugPOS('Error parseando JSON de la respuesta:', err);
      throw new Error('Respuesta no es JSON');
    });
  })
  .then(data => {
    debugPOS('Respuesta de venta (JSON):', data);
    if (data.success) {
      alert('Venta completada exitosamente');
      debugPOS('Venta exitosa, transactionId:', data.transactionId, 'documentType:', data.documentType);
      // Descargar PDF automáticamente
      if (data.transactionId) {
        debugPOS('Llamando a downloadSalePDF con:', data.transactionId, data.documentType);
        downloadSalePDF(data.transactionId, data.documentType);
      } else {
        debugPOS('No se recibió transactionId en la respuesta. No se puede descargar PDF.');
      }
      newSale();
    } else {
      debugPOS('Error reportado por backend al completar venta:', data.message || data);
      alert('Error al completar la venta: ' + (data.message || 'Error desconocido'));
    }
  })
  .catch(error => {
    debugPOS('Error en el proceso de completar venta:', error);
    alert('Error al procesar la venta: ' + (error.message || error));
  });
}

function newSale() {
  cart = [];
  updateCartDisplay();
  clearSearchInput();
  
  // Reset document type to boleta
  document.querySelector('input[name="document_type"][value="boleta"]').checked = true;
  document.getElementById('customer-data').style.display = 'none';
  
  // Reset customer fields
  document.getElementById('customer-rut').value = '';
  document.getElementById('customer-razon-social').value = '';
  document.getElementById('customer-rut-persona').value = '';
  document.getElementById('customer-nombre-persona').value = '';
  
  // Reset payment form
  document.getElementById('amount-received').value = '';
  document.querySelector('input[name="payment_method"][value="efectivo"]').checked = true;
  
  // Show cash payment panel
  document.getElementById('cash-payment').style.display = 'block';
  document.getElementById('card-payment').style.display = 'none';
  document.getElementById('transfer-payment').style.display = 'none';
  
  calculateChange();
  
  // Focus search input
  document.getElementById('product-search').focus();
  
  debugPOS('Nueva venta iniciada');
}

// Función para descargar el PDF de la venta
function downloadSalePDF(ventaId, documentType) {
  try {
    debugPOS('Iniciando descarga de PDF para venta:', { ventaId, documentType });
    // Construir la URL del PDF
    const pdfUrl = '/SistemaBazar/public_html/modules/pos/pdf_controller.php?venta_id=' + ventaId;
    debugPOS('URL de descarga del PDF:', pdfUrl);
    // Crear un enlace temporal para la descarga
    const link = document.createElement('a');
    link.href = pdfUrl;
    link.download = `${documentType}_${ventaId}.pdf`;
    link.target = '_blank';
    // Agregar al DOM temporalmente
    document.body.appendChild(link);
    // Hacer clic en el enlace para iniciar la descarga
    debugPOS('Simulando click en el enlace para descargar PDF...');
    link.click();
    // Remover el enlace del DOM después de un pequeño delay
    setTimeout(() => {
      document.body.removeChild(link);
      debugPOS('Enlace temporal para PDF removido del DOM');
    }, 100);
    debugPOS('Solicitud de descarga de PDF enviada. Si hay problemas, revisar el backend (DOMPDF) y la URL.');
  } catch (error) {
    console.error('Error al descargar PDF:', error);
    debugPOS('Error en downloadSalePDF:', error);
    alert('No se pudo descargar el PDF. Error: ' + error.message);
  }
}

// Make functions globally available for onclick handlers
window.increaseQuantity = increaseQuantity;
window.decreaseQuantity = decreaseQuantity;
window.updateQuantity = updateQuantity;
window.removeFromCart = removeFromCart;
