<?php
namespace Modules\POS\Models;

class POSTransaction {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function save($items, $paymentMethod, $total) {
        try {
            $this->db->begin_transaction();
            
            // Insertar la transacción principal
            $stmt = $this->db->prepare("INSERT INTO ventas (fecha, total, metodo_pago, id_usuario) 
                                       VALUES (NOW(), ?, ?, ?)");
            $userId = $_SESSION['user_id'] ?? 1; // Obtener ID de usuario actual
            $stmt->bind_param("dsi", $total, $paymentMethod, $userId);
            $stmt->execute();
            
            $transactionId = $this->db->insert_id;
            
            // Insertar los ítems de la venta
            foreach ($items as $item) {
                $itemObj = new POSItem($this->db);
                $itemObj->saveToTransaction($transactionId, $item['id'], $item['cantidad'], $item['precio']);
                
                // Actualizar el stock
                $this->updateStock($item['id'], $item['cantidad']);
            }
            
            $this->db->commit();
            return $transactionId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function getById($transactionId) {
        // Obtener datos de la transacción para el recibo
        $stmt = $this->db->prepare("SELECT v.*, u.nombre as vendedor
                                    FROM ventas v
                                    JOIN usuarios u ON v.id_usuario = u.id
                                    WHERE v.id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $transaction = $stmt->get_result()->fetch_assoc();
        
        // Obtener los ítems
        $stmt = $this->db->prepare("SELECT d.*, p.nombre 
                                    FROM detalle_venta d
                                    JOIN productos p ON d.id_producto = p.id
                                    WHERE d.id_venta = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $items = [];
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        $transaction['items'] = $items;
        return $transaction;
    }
    
    private function updateStock($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $productId);
        $stmt->execute();
    }
}
