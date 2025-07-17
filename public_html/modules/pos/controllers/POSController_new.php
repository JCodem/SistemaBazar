<?php

class POSController {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function searchProducts($term) {
        $products = [];
        
        try {
            // Log para debugging
            error_log("POSController - Buscando: " . $term);
            
            // Limpiar el término de búsqueda
            $term = trim($term);
            
            // Primero intentar buscar por código exacto (código de barras o SKU)
            $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                       FROM productos 
                                       WHERE (codigo_barras = ? OR sku = ?) 
                                       AND stock > 0 
                                       LIMIT 1");
            
            if ($stmt) {
                $stmt->bind_param("ss", $term, $term);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // Se encontró un producto exacto por código
                    while ($row = $result->fetch_assoc()) {
                        // Los precios están en pesos, no convertir
                        $products[] = $row;
                    }
                    $stmt->close();
                    error_log("POSController - Productos encontrados por código exacto: " . count($products));
                    return $products;
                }
                $stmt->close();
            }
            
            // Si no se encuentra por código exacto, buscar por nombre, código parcial o SKU parcial
            $searchTerm = "%$term%";
            $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                       FROM productos 
                                       WHERE (nombre LIKE ? OR codigo_barras LIKE ? OR sku LIKE ?) 
                                       AND stock > 0 
                                       ORDER BY 
                                         CASE 
                                           WHEN nombre LIKE ? THEN 1
                                           WHEN codigo_barras LIKE ? THEN 2
                                           WHEN sku LIKE ? THEN 3
                                           ELSE 4
                                         END,
                                         nombre ASC
                                       LIMIT 20");
            
            if ($stmt) {
                $startsWith = "$term%";
                $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $startsWith, $startsWith, $startsWith);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    // Los precios están en pesos, no convertir
                    $products[] = $row;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error en searchProducts: " . $e->getMessage());
            return [];
        }
        
        error_log("POSController - Total productos encontrados: " . count($products));
        return $products;
    }
    
    public function getProductByCode($code) {
        try {
            // Búsqueda exacta por código de barras o SKU
            $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                       FROM productos 
                                       WHERE (codigo_barras = ? OR sku = ?) 
                                       AND stock > 0 
                                       LIMIT 1");
            
            if ($stmt) {
                $stmt->bind_param("ss", $code, $code);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    // Los precios están en pesos, no convertir
                    $stmt->close();
                    return $product;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error en getProductByCode: " . $e->getMessage());
        }
        
        return null;
    }
    
    public function processTransaction($items, $paymentMethod, $total) {
        try {
            // Comenzar transacción
            $this->db->begin_transaction();
            
            // Insertar venta
            $stmt = $this->db->prepare("INSERT INTO ventas (fecha, total, metodo_pago) VALUES (NOW(), ?, ?)");
            $stmt->bind_param("ds", $total, $paymentMethod);
            $stmt->execute();
            $ventaId = $this->db->insert_id;
            $stmt->close();
            
            // Insertar items de venta y actualizar stock
            foreach ($items as $item) {
                // Insertar detalle de venta
                $stmt = $this->db->prepare("INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $ventaId, $item['id'], $item['cantidad'], $item['precio']);
                $stmt->execute();
                $stmt->close();
                
                // Actualizar stock
                $stmt = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['cantidad'], $item['id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Confirmar transacción
            $this->db->commit();
            
            return $ventaId;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->db->rollback();
            error_log("Error en processTransaction: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getReceiptData($transactionId) {
        try {
            // Obtener datos de la venta
            $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id = ?");
            $stmt->bind_param("i", $transactionId);
            $stmt->execute();
            $result = $stmt->get_result();
            $venta = $result->fetch_assoc();
            $stmt->close();
            
            if (!$venta) {
                return null;
            }
            
            // Obtener detalles de la venta
            $stmt = $this->db->prepare("
                SELECT vd.*, p.nombre as producto_nombre 
                FROM venta_detalle vd 
                JOIN productos p ON vd.producto_id = p.id 
                WHERE vd.venta_id = ?
            ");
            $stmt->bind_param("i", $transactionId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $detalles = [];
            while ($row = $result->fetch_assoc()) {
                $detalles[] = $row;
            }
            $stmt->close();
            
            return [
                'venta' => $venta,
                'detalles' => $detalles
            ];
            
        } catch (Exception $e) {
            error_log("Error en getReceiptData: " . $e->getMessage());
            return null;
        }
    }
}
?>
