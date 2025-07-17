<?php
namespace Modules\POS\Models;

class POSTransaction {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function save($items, $paymentMethod, $total) {
        try {
            $this->db->beginTransaction();
            
            // Insertar la transacción principal
            $stmt = $this->db->prepare("INSERT INTO ventas (fecha, total, metodo_pago, id_usuario) 
                                       VALUES (NOW(), :total, :metodo_pago, :id_usuario)");
            $userId = $_SESSION['user_id'] ?? 1;
            $stmt->execute([
                ':total' => $total,
                ':metodo_pago' => $paymentMethod,
                ':id_usuario' => $userId
            ]);
            $transactionId = $this->db->lastInsertId();
            
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
                                    WHERE v.id = :id");
        $stmt->execute([':id' => $transactionId]);
        $transaction = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Obtener los ítems
        $stmt = $this->db->prepare("SELECT d.*, p.nombre 
                                    FROM detalle_venta d
                                    JOIN productos p ON d.id_producto = p.id
                                    WHERE d.id_venta = :id_venta");
        $stmt->execute([':id_venta' => $transactionId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $transaction['items'] = $items;
        return $transaction;
    }
    
    private function updateStock($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
        $stmt->execute([
            ':cantidad' => $quantity,
            ':id' => $productId
        ]);
    }
}
