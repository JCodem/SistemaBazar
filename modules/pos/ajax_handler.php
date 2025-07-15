<?php
// Habilitar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar sesión si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once '../../includes/db.php';
require_once 'controllers/POSController.php';

use Modules\POS\Controllers\POSController;

// Verificar que la conexión a la base de datos esté funcionando
if (!$conn) {
    echo json_encode(['error' => 'No hay conexión a la base de datos']);
    exit;
}

// Crear controlador POS con la conexión existente
$posController = new POSController($conn);

// Procesar la acción solicitada
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'search_products':
        $term = $_POST['term'] ?? '';
        if (empty($term)) {
            echo json_encode(['error' => 'Término de búsqueda vacío']);
            exit;
        }
        echo $posController->searchProducts($term);
        break;
        
    case 'get_product_by_code':
        $code = $_POST['code'] ?? '';
        if (empty($code)) {
            echo json_encode(['error' => 'Código vacío']);
            exit;
        }
        echo $posController->getProductByCode($code);
        break;
        
    case 'process_transaction':
        // Obtener datos JSON del cuerpo de la petición
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if ($data) {
            try {
                $transactionId = $posController->processTransaction(
                    $data['items'], 
                    $data['paymentMethod'], 
                    $data['total']
                );
                
                echo json_encode([
                    'success' => true,
                    'transactionId' => $transactionId
                ]);
            } catch (\Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Datos inválidos'
            ]);
        }
        break;
        
    case 'get_receipt':
        $transactionId = $_POST['transactionId'] ?? 0;
        $receiptData = $posController->getReceiptData($transactionId);
        echo json_encode($receiptData);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no reconocida'
        ]);
}
