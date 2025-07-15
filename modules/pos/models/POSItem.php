<?php
namespace Modules\POS\Models;

class POSItem {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function saveToTransaction($transactionId, $productId, $quantity, $price) {
        $subtotal = $quantity * $price;
        
        $stmt = $this->db->prepare("INSERT INTO detalle_venta 
                                   (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
                                   VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidd", $transactionId, $productId, $quantity, $price, $subtotal);
        $stmt->execute();
        
        return $this->db->insert_id;
    }
}
