$(document).ready(function() {
    console.log('🚀 POS JavaScript cargado correctamente');
    
    // Variables globales
    let cart = [];
    let isScanning = false;
    let scannerBuffer = '';
    let scannerTimeout;
    
    // Configuración del escáner
    const SCANNER_TIMEOUT = 100; // ms entre caracteres para detectar código de barras
    
    // Inicializar carrito al cargar la página
    updateCartDisplay();
    
    // Evento de búsqueda de productos
    $('#search-btn').click(function() {
        console.log('🔍 Botón de búsqueda clickeado');
        searchProducts();
    });
    
    // Captura de teclas para el escáner
    $(document).keydown(function(e) {
        if (!isScanning) return;
        
        // Recolectar caracteres en el buffer
        if (e.which !== 13) { // Si no es Enter
            scannerBuffer += e.key;
            
            // Restablecer el timeout
            clearTimeout(scannerTimeout);
            scannerTimeout = setTimeout(function() {
                scannerBuffer = ''; // Limpiar si pasa mucho tiempo entre caracteres
            }, SCANNER_TIMEOUT);
        } else {
            // Enter recibido - procesar el código escaneado
            if (scannerBuffer.length > 0) {
                processScannedCode(scannerBuffer);
                scannerBuffer = '';
                e.preventDefault(); // Evitar que el Enter haga otras acciones
            }
        }
    });
    
    // Procesar código escaneado
    function processScannedCode(code) {
        // Limpiar el campo de búsqueda y mostrar el código escaneado
        $('#product-search').val(code);
        
        // Buscar producto por código de barras/QR/SKU
        $.ajax({
            url: '../ajax_handler.php?action=get_product_by_code',
            method: 'POST',
            data: { code: code },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        // Producto encontrado - agregar directamente al carrito
                        const product = result.product;
                        addToCart(product.id, product.nombre, parseInt(product.precio));
                        // Notificación de éxito
                        showToast(`Producto agregado: ${product.nombre} - $${parseInt(product.precio)}`);
                        // Limpiar campo de búsqueda
                        $('#product-search').val('').focus();
                    } else {
                        // Código no encontrado
                        showToast('Producto no encontrado con código: ' + code, 'error');
                        beepError();
                    }
                } catch (e) {
                    console.error('Error parsing product data:', e);
                    showToast('Error al procesar respuesta del servidor', 'error');
                }
            },
            error: function() {
                showToast('Error de conexión con el servidor', 'error');
                beepError();
            }
        });
    }
    
    // Activar/desactivar escáner
    $('#toggle-barcode-scanner').click(function() {
        isScanning = !isScanning;
        if (isScanning) {
            $(this).addClass('btn-success').removeClass('btn-outline-success');
            $(this).html('<i class="bi bi-upc-scan"></i> Escáner Activo');
            $('#product-search').attr('disabled', 'disabled');
            $('#product-results').html(''); // Limpiar resultados de búsqueda manual
            showToast('Escáner de códigos activado - listo para escanear');
        } else {
            $(this).removeClass('btn-success').addClass('btn-outline-success');
            $(this).html('<i class="bi bi-upc-scan"></i> Activar Escáner');
            $('#product-search').removeAttr('disabled');
            showToast('Escáner desactivado - modo manual activado');
        }
    });
    
    // Entrada manual
    $('#manual-entry').click(function() {
        isScanning = false;
        $('#toggle-barcode-scanner').removeClass('btn-success').addClass('btn-outline-success');
        $('#toggle-barcode-scanner').html('<i class="bi bi-upc-scan"></i> Activar Escáner');
        $('#product-search').removeAttr('disabled').focus();
        showToast('Modo de entrada manual activado');
    });
    
    // Limpiar carrito
    $('#clear-cart').click(function() {
        if (cart.length > 0) {
            if (confirm('¿Está seguro de que desea limpiar todo el carrito?')) {
                cart = [];
                updateCartDisplay();
                showToast('Carrito limpiado');
            }
        }
    });
    
    // Guardar carrito (funcionalidad básica)
    $('#save-cart').click(function() {
        if (cart.length > 0) {
            localStorage.setItem('saved_cart', JSON.stringify(cart));
            showToast('Carrito guardado localmente');
        } else {
            showToast('No hay items en el carrito para guardar', 'error');
        }
    });
    
    // Sonido de error para código no reconocido
    function beepError() {
        const audioElement = new Audio('/modules/pos/assets/sounds/beep-error.mp3');
        audioElement.play();
    }
    
    // Notificación toast
    function showToast(message, type = 'success') {
        const toastHTML = `
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
        }
        
        $('#toast-container').append(toastHTML);
        const toastElement = $('.toast').last();
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 2000
        });
        toast.show();
    }
    
    // Evento de tecla Enter en campo de búsqueda
    $('#product-search').keypress(function(e) {
        if(e.which === 13) {
            searchProducts();
        }
    });
    
    // Función para buscar productos
    function searchProducts() {
        const searchTerm = $('#product-search').val().trim();
        console.log('🔍 Iniciando búsqueda con término:', searchTerm);
        console.log('📍 Campo encontrado:', $('#product-search').length > 0);
        console.log('📍 Área de resultados encontrada:', $('#product-results').length > 0);
        
        if(searchTerm.length < 2) {
            console.log('⚠️ Término muy corto');
            showToast('Ingrese al menos 2 caracteres para buscar', 'error');
            return;
        }
        
        // Mostrar indicador de carga
        $('#product-results').html('<div class="col-12 text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Cargando...</span></div></div>');
        
        console.log('📡 Enviando petición AJAX a:', '/SistemaBazar/modules/pos/ajax_handler.php?action=search_products');
        
        $.ajax({
            url: '/SistemaBazar/modules/pos/ajax_handler.php?action=search_products',
            method: 'POST',
            data: { term: searchTerm },
            beforeSend: function() {
                console.log('📤 Enviando datos:', { term: searchTerm });
            },
            success: function(response) {
                console.log('✅ Respuesta recibida (raw):', response);
                try {
                    const products = JSON.parse(response);
                    console.log('✅ JSON parseado exitosamente:', products);
                    console.log('📊 Número de productos encontrados:', products.length);
                    displayProductResults(products);
                } catch (e) {
                    console.error('❌ Error parseando JSON:', e);
                    console.error('📄 Respuesta completa:', response);
                    showToast('Error al procesar respuesta del servidor', 'error');
                    $('#product-results').html('<div class="col-12"><div class="alert alert-danger">Error en la respuesta del servidor: ' + e.message + '</div></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error en AJAX:', {xhr, status, error});
                console.error('📄 Response Text:', xhr.responseText);
                console.error('📊 Status Code:', xhr.status);
                showToast('Error de conexión con el servidor', 'error');
                $('#product-results').html('<div class="col-12"><div class="alert alert-danger">Error de conexión: ' + error + ' (Status: ' + xhr.status + ')</div></div>');
            }
        });
    }
    
    // Mostrar resultados de productos
    function displayProductResults(products) {
        console.log('🎯 displayProductResults llamada con:', products);
        console.log('📊 Tipo de datos recibidos:', typeof products);
        console.log('📊 Es array?:', Array.isArray(products));
        console.log('📊 Longitud:', products.length);
        
        let html = '';
        
        if (products.length === 0) {
            console.log('⚠️ No se encontraron productos');
            $('#product-results').html('<div class="col-12"><div class="alert alert-info text-center">No se encontraron productos.</div></div>');
            return;
        }
        
        // Si hay exactamente un producto, agregarlo automáticamente al carrito
        if (products.length === 1) {
            const product = products[0];
            console.log('🎯 Un solo producto encontrado, agregando automáticamente al carrito:', product);
            addToCart(product.id, product.nombre, parseInt(product.precio));
            showToast(`✅ Agregado automáticamente: ${product.nombre} - $${parseInt(product.precio)}`);
            
            // Limpiar campo de búsqueda para siguiente producto
            $('#product-search').val('').focus();
            
            // Mostrar resultado confirmando la adición
            $('#product-results').html(`
                <div class="col-12">
                    <div class="alert alert-success text-center">
                        <i class="bi bi-check-circle fs-4"></i><br>
                        <strong>${product.nombre}</strong><br>
                        <span class="text-muted">Agregado automáticamente al carrito</span><br>
                        <small>Precio: $${parseInt(product.precio)} | Stock: ${product.stock}</small>
                    </div>
                </div>
            `);
            
            // Limpiar resultados después de 2 segundos
            setTimeout(() => {
                $('#product-results').html('');
            }, 2000);
            
            return;
        }
        
        console.log('✅ Construyendo HTML para', products.length, 'productos');
        
        // Si hay múltiples productos, mostrar opciones para seleccionar
        html += '<div class="col-12 mb-3"><div class="alert alert-info text-center"><i class="bi bi-info-circle"></i> Múltiples productos encontrados. Seleccione uno:</div></div>';
        
        products.forEach((product, index) => {
            console.log(`📦 Producto ${index + 1}:`, product);
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card product-card h-100 border-primary">
                        <div class="card-body">
                            <h6 class="card-title text-primary">${product.nombre}</h6>
                            <p class="card-text">
                                <strong class="text-success">Precio:</strong> $${parseInt(product.precio)}<br>
                                <strong class="text-info">Stock:</strong> ${product.stock} unidades<br>
                                ${product.sku ? `<small class="text-muted">SKU: ${product.sku}</small>` : ''}
                            </p>
                            <button class="btn btn-primary btn-sm add-to-cart w-100" 
                                    data-id="${product.id}" 
                                    data-name="${product.nombre}" 
                                    data-price="${product.precio}">
                                <i class="bi bi-cart-plus"></i> Agregar al Carrito
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        console.log('🖼️ HTML generado (primeros 200 chars):', html.substring(0, 200));
        console.log('🎯 Insertando HTML en #product-results');
        
        $('#product-results').html(html);
        
        console.log('🔗 Configurando eventos para botones add-to-cart');
        
        // Evento para agregar al carrito
        $('.add-to-cart').click(function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const price = parseInt($(this).data('price'));
            
            console.log('🛒 Agregando al carrito:', {id, name, price});
            addToCart(id, name, price);
            showToast(`Agregado: ${name} - $${price}`);
            
            // Limpiar resultados y campo de búsqueda después de agregar
            $('#product-results').html('');
            $('#product-search').val('').focus();
        });
        
        console.log('✅ displayProductResults completado');
    }
    
    // Agregar un producto al carrito
    function addToCart(id, name, price) {
        console.log('🛒 addToCart llamada con:', {id, name, price});
        console.log('🛒 Estado actual del carrito:', cart);
        
        // Verificar si el producto ya está en el carrito
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            console.log('🔄 Producto ya existe, incrementando cantidad');
            existingItem.quantity += 1;
            existingItem.total = existingItem.quantity * existingItem.price;
        } else {
            console.log('➕ Agregando nuevo producto al carrito');
            cart.push({
                id: id,
                name: name,
                price: price,
                quantity: 1,
                total: price
            });
        }
        
        console.log('🛒 Nuevo estado del carrito:', cart);
        console.log('🔄 Actualizando visualización del carrito');
        updateCartDisplay();
    }
    
    // Actualizar la visualización del carrito
    function updateCartDisplay() {
        console.log('🔄 Actualizando visualización del carrito');
        let html = '';
        let cartTotal = 0;
        let itemCount = 0;
        
        cart.forEach((item, index) => {
            html += `
                <tr>
                    <td>
                        <strong>${item.name}</strong>
                        ${item.sku ? `<br><small class="text-muted">SKU: ${item.sku}</small>` : ''}
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm quantity-input" 
                               data-index="${index}" value="${item.quantity}" min="1" style="width: 70px">
                    </td>
                    <td class="text-end">$${item.price}</td>
                    <td class="text-end fw-bold">$${item.total}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item" data-index="${index}" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            cartTotal += item.total;
            itemCount += item.quantity;
        });
        
        if (cart.length === 0) {
            html = `
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="bi bi-cart-x fs-3"></i><br>
                        El carrito está vacío<br>
                        <small>Escanea o busca productos para agregar</small>
                    </td>
                </tr>
            `;
        }
        
        $('#cart-items').html(html);
        $('#cart-total').text('$' + cartTotal);
        $('#cart-count').text(itemCount + ' items');
        
        // Actualizar resumen de venta
        updateSalesSummary(itemCount, cartTotal);
        
        // Habilitar/deshabilitar botones según el estado del carrito
        $('#complete-sale').prop('disabled', cart.length === 0);
        $('#clear-cart').prop('disabled', cart.length === 0);
        $('#save-cart').prop('disabled', cart.length === 0);
        
        // Actualizar el monto recibido si está presente
        if ($('#amount-received').length && $('#amount-received').val()) {
            updateChange();
        }
        
        // Evento para cambiar cantidad
        $('.quantity-input').change(function() {
            const index = $(this).data('index');
            const newQuantity = parseInt($(this).val());
            
            if (newQuantity < 1) {
                $(this).val(1);
                return;
            }
            
            cart[index].quantity = newQuantity;
            cart[index].total = cart[index].price * newQuantity;
            
            updateCartDisplay();
            showToast(`Cantidad actualizada: ${cart[index].name}`);
        });
        
        // Evento para eliminar item
        $('.remove-item').click(function() {
            const index = $(this).data('index');
            const itemName = cart[index].name;
            
            if (confirm(`¿Eliminar ${itemName} del carrito?`)) {
                cart.splice(index, 1);
                updateCartDisplay();
                showToast(`Eliminado: ${itemName}`);
            }
        });
        
        console.log('✅ Visualización del carrito actualizada');
    }
    
    // Función para actualizar el cambio
    function updateChange() {
        const amountReceived = parseInt($('#amount-received').val()) || 0;
        const total = parseInt($('#cart-total').text().replace('$', '')) || 0;
        
        let change = amountReceived - total;
        change = change >= 0 ? change : 0;
        
        $('#change-amount').val('$' + change);
        
        // Solo permitir completar venta si el monto recibido es suficiente
        $('#complete-sale').prop('disabled', amountReceived < total || cart.length === 0);
    }
    
    // Función para actualizar el resumen de venta
    function updateSalesSummary(itemCount, cartTotal) {
        console.log('📊 Actualizando resumen de venta:', {itemCount, cartTotal});
        
        // Actualizar elementos del resumen
        $('#summary-items').text(itemCount);
        $('#summary-total').text('$' + cartTotal);
        
        // También actualizar el input de monto recibido para auto-calcular cambio
        if ($('#amount-received').length) {
            const currentReceived = parseInt($('#amount-received').val()) || 0;
            if (currentReceived > 0) {
                updateChange();
            }
        }
        
        console.log('✅ Resumen de venta actualizado');
    }
    
    // Cargar carrito guardado al inicializar (si existe)
    function loadSavedCart() {
        const savedCart = localStorage.getItem('saved_cart');
        if (savedCart) {
            try {
                cart = JSON.parse(savedCart);
                updateCartDisplay();
                showToast('Carrito guardado cargado');
            } catch (e) {
                console.error('Error loading saved cart:', e);
                localStorage.removeItem('saved_cart');
            }
        }
    }
    
    // Llamar al cargar la página
    loadSavedCart();
    
    // Funcionalidad adicional para el resumen de venta
    
    // Evento para cambio en método de pago
    $(document).on('change', '#payment-method', function() {
        const method = $(this).val();
        console.log('💳 Método de pago cambiado a:', method);
        
        // Actualizar cálculo del cambio si es efectivo
        if (method === 'efectivo') {
            updateChange();
        }
    });
    
    // Evento para cambio en monto recibido
    $(document).on('input', '#amount-received', function() {
        console.log('💰 Monto recibido cambiado:', $(this).val());
        updateChange();
    });
    
    // Evento para botón de pago exacto
    $(document).on('click', '#quick-payment', function() {
        const total = parseInt($('#cart-total').text().replace('$', '')) || 0;
        if (total > 0) {
            console.log('⚡ Configurando pago exacto por:', total);
            $('#payment-method').val('efectivo');
            $('#amount-received').val(total);
            updateChange();
            showToast(`Configurado pago exacto: $${total}`);
        }
    });
    
    // Evento para completar venta
    $(document).on('click', '#complete-sale', function() {
        if (cart.length === 0) {
            showToast('No hay productos en el carrito', 'error');
            return;
        }
        
        const paymentMethod = $('#payment-method').val();
        const total = parseInt($('#cart-total').text().replace('$', '')) || 0;
        
        console.log('💸 Intentando completar venta:', {paymentMethod, total, cart});
        
        if (paymentMethod === 'efectivo') {
            const amountReceived = parseInt($('#amount-received').val()) || 0;
            if (amountReceived < total) {
                showToast('El monto recibido es insuficiente', 'error');
                return;
            }
        }
        
        // Simular procesamiento de venta
        $(this).prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Procesando...');
        
        setTimeout(() => {
            // Mostrar mensaje de éxito
            const change = paymentMethod === 'efectivo' ? 
                (parseInt($('#amount-received').val()) || 0) - total : 0;
            
            let successMessage = `✅ Venta completada exitosamente!\n`;
            successMessage += `Total: $${total}\n`;
            successMessage += `Método: ${paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1)}\n`;
            if (paymentMethod === 'efectivo' && change > 0) {
                successMessage += `Cambio a devolver: $${change}`;
            }
            
            alert(successMessage);
            
            // Limpiar carrito y resetear interfaz
            cart = [];
            updateCartDisplay();
            $('#payment-method').val('efectivo');
            $('#amount-received').val('');
            $('#change-amount').val('0');
            $('#product-search').val('').focus();
            
            $(this).prop('disabled', false).html('<i class="bi bi-check-circle"></i> Completar Venta');
            
            showToast('Nueva venta lista');
            
        }, 1500);
    });
    
    // Evento para nueva venta
    $(document).on('click', '#new-sale', function() {
        if (cart.length > 0) {
            if (confirm('¿Está seguro de que desea iniciar una nueva venta? Se perderá el carrito actual.')) {
                cart = [];
                updateCartDisplay();
                $('#payment-method').val('efectivo');
                $('#amount-received').val('');
                $('#change-amount').val('0');
                $('#product-search').val('').focus();
                showToast('Nueva venta iniciada');
            }
        } else {
            showToast('Ya está en una nueva venta');
        }
    });
    
    console.log('✅ POS JavaScript inicializado completamente');

    // Exponer funciones para payment-handler.js
    window.posModule = {
        cart: cart,
        updateCartDisplay: updateCartDisplay,
        updateChange: updateChange,
        updateSalesSummary: updateSalesSummary,
        addToCart: addToCart,
        clearCart: function() {
            cart = [];
            updateCartDisplay();
        },
        getCartTotal: function() {
            return cart.reduce((total, item) => total + item.total, 0);
        },
        getCartItemCount: function() {
            return cart.reduce((count, item) => count + item.quantity, 0);
        }
    };
});
