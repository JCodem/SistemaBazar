$(document).ready(function() {
    // Actualizar resumen de venta cuando cambie el carrito
    function updateSummary() {
        const cart = window.posModule.cart;
        let totalItems = 0;
        let totalAmount = 0;
        
        cart.forEach(item => {
            totalItems += item.quantity;
            totalAmount += item.total;
        });
        
        $('#summary-items').text(totalItems);
        $('#summary-total').text('$' + totalAmount);
    }
    
    // Llamar updateSummary cada vez que se actualice el carrito
    const originalUpdateCart = window.posModule.updateCartDisplay;
    window.posModule.updateCartDisplay = function() {
        originalUpdateCart();
        updateSummary();
    };
    
    // Mostrar/ocultar opciones de pago según método seleccionado
    $('#payment-method').change(function() {
        const method = $(this).val();
        
        // Ocultar todos los paneles de pago
        $('.payment-detail').hide();
        
        if (method === 'efectivo') {
            $('#cash-payment').show();
        } else if (method === 'tarjeta') {
            $('#card-payment').show();
        } else if (method === 'transferencia') {
            $('#transfer-payment').show();
        }
        
        // Habilitar/deshabilitar botón según método
        updateCompleteButton();
    });
    
    // Calcular cambio para pago en efectivo
    $('#amount-received').on('input', function() {
        const amountReceived = parseInt($(this).val()) || 0;
        const total = parseInt($('#cart-total').text().replace('$', '')) || 0;
        
        let change = amountReceived - total;
        change = change >= 0 ? change : 0;
        
        $('#change-amount').val(change);
        
        updateCompleteButton();
    });
    
    // Actualizar estado del botón Completar Venta
    function updateCompleteButton() {
        const paymentMethod = $('#payment-method').val();
        const cart = window.posModule.cart;
        const total = parseInt($('#cart-total').text().replace('$', '')) || 0;
        let canComplete = false;
        
        if (cart.length === 0) {
            canComplete = false;
        } else if (paymentMethod === 'efectivo') {
            const amountReceived = parseInt($('#amount-received').val()) || 0;
            canComplete = amountReceived >= total;
        } else {
            // Para tarjeta y transferencia, asumir que están listas
            canComplete = total > 0;
        }
        
        $('#complete-sale').prop('disabled', !canComplete);
    }
    
    // Pago exacto rápido
    $('#quick-payment').click(function() {
        const total = parseInt($('#cart-total').text().replace('$', '')) || 0;
        if (total > 0) {
            $('#payment-method').val('efectivo').trigger('change');
            $('#amount-received').val(total).trigger('input');
        }
    });
    
    // Nueva venta
    $('#new-sale').click(function() {
        if (window.posModule.cart.length > 0) {
            if (confirm('¿Está seguro de que desea iniciar una nueva venta? Se perderá el carrito actual.')) {
                window.posModule.clearCart();
                $('#payment-method').val('efectivo').trigger('change');
                $('#amount-received').val('');
                showToast('Nueva venta iniciada');
            }
        }
    });
    
    // Suspender venta
    $('#hold-sale').click(function() {
        if (window.posModule.cart.length > 0) {
            const timestamp = new Date().toLocaleString();
            localStorage.setItem('held_sale_' + Date.now(), JSON.stringify({
                cart: window.posModule.cart,
                timestamp: timestamp
            }));
            showToast('Venta suspendida y guardada');
        }
    });
    
    // Cancelar venta
    $('#cancel-sale').click(function() {
        if (window.posModule.cart.length > 0) {
            if (confirm('¿Está seguro de que desea cancelar la venta actual?')) {
                window.posModule.clearCart();
                $('#payment-method').val('efectivo').trigger('change');
                $('#amount-received').val('');
                showToast('Venta cancelada');
            }
        }
    });
    
    // Completar la venta
    $('#complete-sale').click(function() {
        const paymentMethod = $('#payment-method').val();
        const total = parseInt($('#cart-total').text().replace('$', '')) || 0;
        
        if (window.posModule.cart.length === 0) {
            showToast('No hay productos en el carrito', 'error');
            return;
        }
        
        // Validación para pago en efectivo
        if (paymentMethod === 'efectivo') {
            const amountReceived = parseInt($('#amount-received').val()) || 0;
            if (amountReceived < total) {
                showToast('El monto recibido es insuficiente', 'error');
                return;
            }
        }
        
        // Preparar los datos para enviar
        const saleData = {
            items: window.posModule.cart.map(item => ({
                id: item.id,
                cantidad: item.quantity,
                precio: item.price
            })),
            paymentMethod: paymentMethod,
            total: total
        };
        
        // Deshabilitar botón durante el procesamiento
        $('#complete-sale').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Procesando...');
        
        // Enviar la transacción al servidor
        $.ajax({
            url: '../ajax_handler.php?action=process_transaction',
            method: 'POST',
            data: JSON.stringify(saleData),
            contentType: 'application/json',
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    
                    if (result.success) {
                        // Mostrar recibo
                        loadReceipt(result.transactionId);
                        
                        // Limpiar carrito y reiniciar interfaz
                        window.posModule.clearCart();
                        $('#payment-method').val('efectivo').trigger('change');
                        $('#amount-received').val('');
                        
                        showToast('¡Venta completada exitosamente!', 'success');
                    } else {
                        showToast('Error al procesar la venta: ' + result.message, 'error');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    showToast('Error en la respuesta del servidor', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error processing transaction:', error);
                showToast('Error al procesar la transacción', 'error');
            },
            complete: function() {
                // Rehabilitar botón
                $('#complete-sale').prop('disabled', false).html('<i class="bi bi-check-circle"></i> Completar Venta');
                updateCompleteButton(); // Revisar estado nuevamente
            }
        });
    });
    
    // Función auxiliar para mostrar notificaciones
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
        
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
            toastContainer = document.getElementById('toast-container');
        }
        
        $(toastContainer).append(toastHTML);
        const toastElement = $('.toast').last();
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();
    }
    
    // Cargar datos del recibo
    function loadReceipt(transactionId) {
        $.ajax({
            url: '../ajax_handler.php?action=get_receipt',
            method: 'POST',
            data: { transactionId: transactionId },
            success: function(response) {
                try {
                    const receiptData = JSON.parse(response);
                    displayReceipt(receiptData);
                    
                    // Limpiar carrito después de venta exitosa
                    window.posModule.cart = [];
                    window.posModule.updateCartDisplay();
                    $('#amount-received').val('');
                    $('#change-amount').val('$0.00');
                    
                } catch (e) {
                    console.error('Error parsing receipt data:', e);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading receipt:', error);
            }
        });
    }
    
    // Mostrar el recibo en modal
    function displayReceipt(data) {
        let html = `
            <div class="text-center mb-3">
                <h4>SISTEMA BAZAR</h4>
                <p>Recibo de Venta #${data.id}</p>
                <p>Fecha: ${data.fecha}</p>
                <p>Vendedor: ${data.vendedor}</p>
            </div>
            
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.items.forEach(item => {
            html += `
                <tr>
                    <td>${item.nombre}</td>
                    <td>${item.cantidad}</td>
                    <td>$${parseFloat(item.precio_unitario).toFixed(2)}</td>
                    <td>$${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total:</th>
                        <th>$${parseFloat(data.total).toFixed(2)}</th>
                    </tr>
                </tfoot>
            </table>
            
            <div class="text-center mt-3">
                <p>Método de pago: ${data.metodo_pago}</p>
                <p>¡Gracias por su compra!</p>
            </div>
        `;
        
        $('#receipt-content').html(html);
        
        // Mostrar el modal
        new bootstrap.Modal(document.getElementById('receiptModal')).show();
    }
    
    // Imprimir recibo
    $('#print-receipt').click(function() {
        const content = document.getElementById('receipt-content').innerHTML;
        const printWindow = window.open('', '_blank');
        
        printWindow.document.write(`
            <html>
                <head>
                    <title>Recibo de Venta</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
                    <style>
                        body { font-family: Arial, sans-serif; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="container my-4">
                        ${content}
                        <button class="btn btn-primary no-print mt-3" onclick="window.print()">
                            Imprimir
                        </button>
                    </div>
                </body>
            </html>
        `);
        
        printWindow.document.close();
    });
});
