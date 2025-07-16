<?php

class POSController {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }

    public function searchProducts($term) {
        $products = [];

        try {
            error_log("POSController - Buscando: " . $term);
            $term = trim($term);

            // Buscar por código exacto (código de barras o SKU)
            $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                        FROM productos 
                                        WHERE (codigo_barras = :term OR sku = :term) 
                                        AND stock > 0 
                                        LIMIT 1");
            $stmt->execute([':term' => $term]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (count($result) > 0) {
                foreach ($result as $row) {
                    $products[] = $row;
                }
            } else {
                // Buscar por nombre o coincidencias parciales
                $searchTerm = "%$term%";
                $startsWith = "$term%";
                $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                            FROM productos 
                                            WHERE (nombre LIKE :searchTerm OR codigo_barras LIKE :searchTerm OR sku LIKE :searchTerm)
                                            AND stock > 0 
                                            ORDER BY 
                                              CASE 
                                                WHEN nombre LIKE :startsWith THEN 1
                                                WHEN codigo_barras LIKE :startsWith THEN 2
                                                WHEN sku LIKE :startsWith THEN 3
                                                ELSE 4
                                              END,
                                              nombre ASC
                                            LIMIT 20");
                $stmt->execute([
                    ':searchTerm' => $searchTerm,
                    ':startsWith' => $startsWith
                ]);
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($result as $row) {
                    $products[] = $row;
                }
            }
        } catch (\Exception $e) {
            error_log("Error en searchProducts: " . $e->getMessage());
            return [];
        }

        error_log("POSController - Total productos encontrados: " . count($products));
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
                return json_encode(['success' => true, 'product' => $product]);
            }
        } catch (\Exception $e) {
            error_log("Error en getProductByCode: " . $e->getMessage());
        }

        return json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }

    public function processTransaction($items, $paymentMethod, $total) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO ventas (fecha, total, metodo_pago) VALUES (NOW(), ?, ?)");
            $stmt->execute([$total, $paymentMethod]);
            $ventaId = $this->db->lastInsertId();

            foreach ($items as $item) {
                $stmt = $this->db->prepare("INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio) 
                                            VALUES (?, ?, ?, ?)");
                $stmt->execute([$ventaId, $item['id'], $item['cantidad'], $item['precio']]);

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
