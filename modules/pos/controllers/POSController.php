<?php
namespace Modules\POS\Controllers;

use Modules\POS\Models\POSTransaction;
use Modules\POS\Models\POSItem;

class POSController {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function searchProducts($term) {
        $products = [];
        
        try {
            // Buscar por código exacto (código de barras o SKU) usando PDO
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
                // Si no se encuentra por código, buscar por nombre
                $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                           FROM productos 
                                           WHERE nombre LIKE :nombre 
                                           AND stock > 0 
                                           LIMIT 10");
                $searchTerm = "%$term%";
                $stmt->execute([':nombre' => $searchTerm]);
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($result as $row) {
                    $products[] = $row;
                }
            }
        } catch (\Exception $e) {
            error_log("Error en searchProducts: " . $e->getMessage());
            return json_encode(['error' => 'Error en la búsqueda: ' . $e->getMessage()]);
        }
        
        return json_encode($products);
    }
    
    public function getProductByCode($code) {
        try {
            // Búsqueda exacta por código de barras o SKU usando PDO
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
            return json_encode(['success' => false, 'message' => 'Error en la búsqueda: ' . $e->getMessage()]);
        }
        return json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    }
    
    public function processTransaction($items, $paymentMethod, $total) {
        $transaction = new POSTransaction($this->db);
        return $transaction->save($items, $paymentMethod, $total);
    }
    
    public function getReceiptData($transactionId) {
        $transaction = new POSTransaction($this->db);
        return $transaction->getById($transactionId);
    }
}
