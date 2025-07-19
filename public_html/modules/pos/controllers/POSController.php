<?php

class POSController {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    public function searchProducts($term) {
        $products = [];
        try {
            $term = trim($term);
            $searchTerm = "%{$term}%";

            $sql = "SELECT id, nombre, precio, stock, codigo_barras, sku 
                    FROM productos 
                    WHERE (sku LIKE ? OR codigo_barras LIKE ? OR nombre LIKE ?)
                    AND stock > 0 
                    ORDER BY nombre ASC
                    LIMIT 20";
            
            // Debug logging
            error_log("Search term: '{$term}', Search pattern: '{$searchTerm}'");
            error_log("SQL Query: {$sql}");
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            
            $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Debug logging
            error_log("Found " . count($products) . " products");

        } catch (\Exception $e) {
            error_log("Error en searchProducts: " . $e->getMessage());
            return [];
        }

        return $products;
    }

    public function getProductByCode($code) {
        try {
            $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                        FROM productos 
                                        WHERE (codigo_barras = :code OR sku = :code) 
                                        AND stock > 0 
                                        LIMIT 1");
            $stmt->execute([':code' => $code]);
            $product = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($product) {
                // Devolver array de producto para que ajax_handler construya JSON
                return $product;
            }
            
            // Producto no encontrado
            return null;
        } catch (\Exception $e) {
            error_log("Error en getProductByCode: " . $e->getMessage());
            return null;
        }
    }

   public function processTransaction($items, $paymentMethod, $total, $documentType = 'boleta', $userId = null, $sessionId = null, $customerData = null) {
    try {
        error_log("[POS] Iniciando processTransaction: " . json_encode([
            'items' => $items,
            'paymentMethod' => $paymentMethod,
            'total' => $total,
            'documentType' => $documentType,
            'userId' => $userId,
            'sessionId' => $sessionId,
            'customerData' => $customerData
        ]));
        $this->db->beginTransaction();

        // Get user ID from session if not provided
        if (!$userId && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            error_log("[POS] userId resuelto desde sesión: $userId");
        } else {
            error_log("[POS] userId recibido: $userId");
        }

        // Obtener sesion_caja_id si no se recibe
        if (!$sessionId && $userId) {
            $stmt = $this->db->prepare("SELECT id FROM sesiones_caja WHERE usuario_id = ? AND estado = 'abierta' ORDER BY fecha_apertura DESC LIMIT 1");
            $stmt->execute([$userId]);
            $sesion = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($sesion && isset($sesion['id'])) {
                $sessionId = $sesion['id'];
                error_log("[POS] sessionId resuelto: $sessionId");
            } else {
                error_log("[POS] ERROR: No hay sesión de caja abierta para usuario $userId");
                throw new \Exception("No hay una sesión de caja abierta para este usuario. No se puede completar la venta.");
            }
        } else {
            error_log("[POS] sessionId recibido: $sessionId");
        }

        // Validación estricta para factura
        $clienteId = null;
        if ($documentType === 'factura') {
            error_log("[POS] Validando datos de cliente para factura: " . json_encode($customerData));
            if (
                !$customerData ||
                empty($customerData['rut']) ||
                empty($customerData['razon_social']) ||
                empty($customerData['direccion'])
            ) {
                error_log("[POS] ERROR: Datos de cliente incompletos para factura");
                throw new \Exception("Para facturas, los datos del cliente (RUT, Razón Social y Dirección) son obligatorios.");
            }
            // Procesar cliente como antes
            error_log("[POS] Procesando cliente para factura: " . print_r($customerData, true));
            $stmt = $this->db->prepare("SELECT id FROM clientes WHERE rut_empresa = ? LIMIT 1");
            $stmt->execute([$customerData['rut']]);
            $existingClient = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($existingClient) {
                $clienteId = $existingClient['id'];
                error_log("[POS] Cliente existente encontrado con ID: " . $clienteId);
                if (!empty($customerData['direccion']) || !empty($customerData['rut_persona']) || !empty($customerData['nombre_persona'])) {
                    $updateStmt = $this->db->prepare("UPDATE clientes SET direccion = COALESCE(?, direccion), rut = COALESCE(?, rut), nombre = COALESCE(?, nombre) WHERE id = ?");
                    $updateStmt->execute([
                        $customerData['direccion'] ?: null,
                        $customerData['rut_persona'] ?: null,
                        $customerData['nombre_persona'] ?: null,
                        $clienteId
                    ]);
                    error_log("[POS] Cliente actualizado con nueva información de contacto");
                }
            } else {
                error_log("[POS] Creando nuevo cliente con dirección: " . ($customerData['direccion'] ?: 'VACÍA'));
                $stmt = $this->db->prepare("INSERT INTO clientes (rut_empresa, razon_social, direccion, rut, nombre, tipo_cliente) 
                                           VALUES (?, ?, ?, ?, ?, 'empresa')");
                $stmt->execute([
                    $customerData['rut'],
                    $customerData['razon_social'],
                    $customerData['direccion'] ?: null,
                    $customerData['rut_persona'] ?: null,
                    $customerData['nombre_persona'] ?: null
                ]);
                $clienteId = $this->db->lastInsertId();
                error_log("[POS] Nuevo cliente creado con ID: " . $clienteId);
            }
        }

        $documentNumber = strtoupper($documentType) . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        error_log("[POS] Insertando venta: usuario_id=$userId, cliente_id=$clienteId, total=$total, metodo_pago=$paymentMethod, tipo_documento=$documentType, numero_documento=$documentNumber, sesion_caja_id=$sessionId");
        $stmt = $this->db->prepare("INSERT INTO ventas (usuario_id, cliente_id, total, metodo_pago, tipo_documento, numero_documento, fecha, sesion_caja_id) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$userId, $clienteId, $total, $paymentMethod, $documentType, $documentNumber, $sessionId]);
        $ventaId = $this->db->lastInsertId();
        error_log("[POS] Venta insertada con ID: $ventaId");

        foreach ($items as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            error_log("[POS] Insertando detalle: venta_id=$ventaId, producto_id={$item['id']}, cantidad={$item['cantidad']}, precio_unitario={$item['precio']}, subtotal=$subtotal");
            $stmt = $this->db->prepare("INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                        VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ventaId, $item['id'], $item['cantidad'], $item['precio'], $subtotal]);
            error_log("[POS] Detalle insertado para producto_id={$item['id']}");

            $stmt = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['cantidad'], $item['id']]);
            error_log("[POS] Stock actualizado para producto_id={$item['id']}");
        }

        $this->db->commit();
        error_log("[POS] Commit exitoso para venta $ventaId");
        if ($clienteId) {
            error_log("[POS] Venta {$ventaId} creada con cliente {$clienteId} para {$documentType}");
        } else {
            error_log("[POS] Venta {$ventaId} creada sin cliente para {$documentType}");
        }
        return $ventaId;
    } catch (\Exception $e) {
        $this->db->rollBack();
        error_log("[POS] ERROR en processTransaction: " . $e->getMessage());
        throw $e;
    }
}

    public function getReceiptData($transactionId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id = ?");
            $stmt->execute([$transactionId]);
            $venta = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$venta) {
                return null;
            }

            $stmt = $this->db->prepare("SELECT vd.*, p.nombre as producto_nombre 
                                        FROM venta_detalle vd 
                                        JOIN productos p ON vd.producto_id = p.id 
                                        WHERE vd.venta_id = ?");
            $stmt->execute([$transactionId]);
            $detalles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'venta' => $venta,
                'detalles' => $detalles
            ];
        } catch (\Exception $e) {
            error_log("Error en getReceiptData: " . $e->getMessage());
            return null;
        }
    }
}
?>
