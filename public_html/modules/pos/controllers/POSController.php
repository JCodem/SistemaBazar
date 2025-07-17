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

    public function processTransaction($items, $paymentMethod, $total, $documentType = 'boleta', $userId = null, $sessionId = null) {
        try {
            $this->db->beginTransaction();

            // Get user ID from session if not provided
            if (!$userId && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }

            // Obtener el id de la sesión de caja abierta si no se pasa $sessionId
            if (!$sessionId && $userId) {
                $stmtCaja = $this->db->prepare("SELECT id FROM sesiones_caja WHERE usuario_id = ? AND estado = 'abierta' LIMIT 1");
                $stmtCaja->execute([$userId]);
                $rowCaja = $stmtCaja->fetch(\PDO::FETCH_ASSOC);
                if ($rowCaja && isset($rowCaja['id'])) {
                    $sessionId = $rowCaja['id'];
                } else {
                    throw new \Exception('No hay sesión de caja abierta para este usuario.');
                }
            }

            // Generar número de documento
            $documentNumber = strtoupper($documentType) . '-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $stmt = $this->db->prepare("INSERT INTO ventas (usuario_id, total, metodo_pago, tipo_documento, numero_documento, fecha, sesion_caja_id) 
                                       VALUES (?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([$userId, $total, $paymentMethod, $documentType, $documentNumber, $sessionId]);
            $ventaId = $this->db->lastInsertId();

            foreach ($items as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                $stmt = $this->db->prepare("INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                            VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$ventaId, $item['id'], $item['cantidad'], $item['precio'], $subtotal]);

                $stmt = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['cantidad'], $item['id']]);
            }

            $this->db->commit();
            return $ventaId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error en processTransaction: " . $e->getMessage());
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
