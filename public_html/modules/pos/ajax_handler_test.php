<?php
// Temporary version without middleware for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurar headers para JSON
header('Content-Type: application/json');

// Incluir archivos necesarios (sin middleware por ahora)
require_once '../../../includes/db.php';
require_once 'controllers/POSController.php';

// Verificar que la conexión a la base de datos esté funcionando
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'No hay conexión a la base de datos']);
    exit;
}

// Crear controlador POS con la conexión existente
$posController = new POSController($conn);

// Procesar la acción solicitada - puede venir en POST, GET o JSON
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Si no hay acción en POST/GET, verificar en JSON body
if (empty($action)) {
    $jsonData = file_get_contents('php://input');
    if (!empty($jsonData)) {
        $data = json_decode($jsonData, true);
        if ($data && isset($data['action'])) {
            $action = $data['action'];
        }
    }
}

// Alias para compatibilidad con JS
if ($action === 'search_products') {
    $action = 'search';
}

// Log para debugging
error_log("POS AJAX - Acción recibida: " . $action);
error_log("POS AJAX - POST data: " . print_r($_POST, true));
if (isset($data)) {
    error_log("POS AJAX - JSON data: " . print_r($data, true));
}

switch ($action) {
    case 'search':
        // Leer término de búsqueda desde 'query' o 'term'
        $query = trim($_POST['query'] ?? $_POST['term'] ?? '');
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
        
    case 'complete_sale':
        // Usar los datos JSON ya parseados si están disponibles
        if (!isset($data)) {
            $jsonData = file_get_contents('php://input');
            $data = json_decode($jsonData, true);
        }
        
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
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Acción no reconocida: ' . $action
        ]);
}
?>
