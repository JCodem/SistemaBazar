<?php
// Habilitar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurar headers para JSON
header('Content-Type: application/json');

// Iniciar sesión si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos necesarios
require_once '../../includes/db.php';
require_once 'controllers/POSController.php';

// Verificar que la conexión a la base de datos esté funcionando
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'No hay conexión a la base de datos']);
    exit;
}

// Crear controlador POS con la conexión existente
$posController = new POSController($conn);

// Procesar la acción solicitada - puede venir en POST o GET
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Log para debugging
error_log("POS AJAX - Acción recibida: " . $action);
error_log("POS AJAX - POST data: " . print_r($_POST, true));

switch ($action) {
    case 'search':
        $query = $_POST['query'] ?? '';
        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Término de búsqueda vacío']);
            exit;
        }
        
        error_log("POS AJAX - Buscando: " . $query);
        
        try {
            $productos = $posController->searchProducts($query);
            echo json_encode([
                'success' => true,
                'productos' => $productos,
                'count' => count($productos)
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error en búsqueda: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'get_product_by_code':
        $code = $_POST['code'] ?? '';
        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Código vacío']);
            exit;
        }
        
        try {
            $producto = $posController->getProductByCode($code);
            echo json_encode([
                'success' => true,
                'producto' => $producto
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar producto: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'complete_sale':
        // Obtener datos JSON del cuerpo de la petición
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if ($data) {
            try {
                $transactionId = $posController->processTransaction(
                    $data['items'], 
                    $data['payment_method'], 
                    $data['total']
                );
                
                echo json_encode([
                    'success' => true,
                    'transactionId' => $transactionId
                ]);
            } catch (Exception $e) {
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
