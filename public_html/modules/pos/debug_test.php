<?php
// Simple test for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== POS AJAX Debug Test ===\n";

echo "1. Testing include paths...\n";
if (file_exists('../../../includes/middleware_unificado.php')) {
    echo "✅ middleware_unificado.php found\n";
} else {
    echo "❌ middleware_unificado.php NOT found\n";
}

if (file_exists('../../../includes/db.php')) {
    echo "✅ db.php found\n";
} else {
    echo "❌ db.php NOT found\n";
}

if (file_exists('controllers/POSController.php')) {
    echo "✅ POSController.php found\n";
} else {
    echo "❌ POSController.php NOT found\n";
}

echo "\n2. Testing session...\n";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ User logged in: " . $_SESSION['user_id'] . "\n";
    echo "✅ User role: " . ($_SESSION['user_role'] ?? 'not set') . "\n";
} else {
    echo "❌ No user session found\n";
}

echo "\n3. Testing database connection...\n";
try {
    require_once '../../../includes/db.php';
    if (isset($conn) && $conn) {
        echo "✅ Database connection successful\n";
        
        // Test query
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM productos WHERE stock > 0");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✅ Found " . $result['count'] . " products with stock\n";
        
        // Show some sample products
        $stmt = $conn->prepare("SELECT nombre, sku, codigo_barras, stock FROM productos WHERE stock > 0 LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll();
        echo "Sample products:\n";
        foreach ($products as $product) {
            echo "  - {$product['nombre']} (SKU: {$product['sku']}, Barcode: {$product['codigo_barras']}, Stock: {$product['stock']})\n";
        }
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing POS Controller...\n";
try {
    require_once 'controllers/POSController.php';
    
    // Test that both use same connection
    echo "Connection info - External: " . get_class($conn) . "\n";
    
    $posController = new POSController($conn);
    
    // Test direct SQL first
    echo "Testing direct SQL...\n";
    $stmt = $conn->prepare("SELECT id, nombre FROM productos WHERE nombre LIKE '%Arroz%' AND stock > 0 LIMIT 3");
    $stmt->execute();
    $directResults = $stmt->fetchAll();
    echo "Direct SQL found " . count($directResults) . " products\n";
    foreach ($directResults as $result) {
        echo "  - {$result['nombre']}\n";
    }
    
    // Test a simple search method to see if the issue is in POSController itself
    echo "\nTesting POSController database access...\n";
    try {
        $reflection = new ReflectionClass($posController);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $controllerDb = $dbProperty->getValue($posController);
        echo "POSController DB connection: " . get_class($controllerDb) . "\n";
        
        // Test direct query through controller's db connection
        $stmt2 = $controllerDb->prepare("SELECT COUNT(*) as count FROM productos WHERE stock > 0");
        $stmt2->execute();
        $result2 = $stmt2->fetch();
        echo "Controller DB has " . $result2['count'] . " products with stock\n";
        
    } catch (Exception $e) {
        echo "Error checking controller DB: " . $e->getMessage() . "\n";
    }
    
    // Test with different search terms
    $searchTerms = ['Arroz'];
    foreach ($searchTerms as $searchTerm) {
        $products = $posController->searchProducts($searchTerm);
        echo "✅ Search for '{$searchTerm}': found " . count($products) . " products\n";
        if (count($products) > 0) {
            echo "  First result: {$products[0]['nombre']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ POS Controller error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
