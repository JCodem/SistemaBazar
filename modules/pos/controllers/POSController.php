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
                        $products[] = $row;
                    }
                } else {
                    // Si no se encuentra por código, buscar por nombre
                    $stmt = $this->db->prepare("SELECT id, nombre, precio, stock, codigo_barras, sku 
                                               FROM productos 
                                               WHERE nombre LIKE ? 
                                               AND stock > 0 
                                               LIMIT 10");
                    $searchTerm = "%$term%";
                    $stmt->bind_param("s", $searchTerm);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $products[] = $row;
                    }
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error en searchProducts: " . $e->getMessage());
            return json_encode(['error' => 'Error en la búsqueda: ' . $e->getMessage()]);
        }
        
        return json_encode($products);
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
                    $stmt->close();
                    return json_encode(['success' => true, 'product' => $product]);
                }
                $stmt->close();
            }
        } catch (Exception $e) {
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
