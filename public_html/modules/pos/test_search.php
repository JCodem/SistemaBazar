<?php
require_once '../../includes/db.php';

$query = $_GET['q'] ?? 'test';

echo "<h3>Test de Búsqueda POS</h3>";
echo "<p>Buscando: <strong>$query</strong></p>";

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Mostrar estructura de la tabla
echo "<h4>Estructura de la tabla productos:</h4>";
$result = $conn->query("DESCRIBE productos");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error al obtener estructura: " . $conn->error;
}

// Contar productos
echo "<h4>Total de productos en la tabla:</h4>";
$result = $conn->query("SELECT COUNT(*) as total FROM productos");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Total: <strong>{$row['total']}</strong> productos</p>";
}

// Mostrar algunos productos de ejemplo
echo "<h4>Primeros 5 productos:</h4>";
$result = $conn->query("SELECT id, nombre, precio, stock, sku, codigo_barras FROM productos LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>SKU</th><th>Código</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td>{$row['precio']}</td>";
        echo "<td>{$row['stock']}</td>";
        echo "<td>" . ($row['sku'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['codigo_barras'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>No hay productos en la tabla</strong></p>";
}

// Probar búsqueda
echo "<h4>Resultado de búsqueda con: '$query'</h4>";
$searchTerm = "%$query%";
$sql = "SELECT id, nombre, precio, stock, sku, codigo_barras FROM productos WHERE nombre LIKE ? OR sku LIKE ? OR codigo_barras LIKE ? LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

echo "<p>SQL ejecutado: <code>$sql</code></p>";
echo "<p>Parámetro de búsqueda: <code>$searchTerm</code></p>";

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>SKU</th><th>Código</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nombre']}</td>";
        echo "<td>{$row['precio']}</td>";
        echo "<td>{$row['stock']}</td>";
        echo "<td>" . ($row['sku'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['codigo_barras'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><strong>No se encontraron productos con esa búsqueda</strong></p>";
}

// Verificar si existe el POSController
echo "<h4>Verificación del POSController:</h4>";
$controllerPath = '../controllers/POSController.php';
if (file_exists($controllerPath)) {
    echo "<p>✅ POSController.php existe</p>";
    
    // Probar el controlador
    require_once $controllerPath;
    
    try {
        $controller = new POSController();
        $productos = $controller->searchProducts($query);
        
        echo "<p>✅ POSController instanciado correctamente</p>";
        echo "<p>Productos encontrados por POSController: <strong>" . count($productos) . "</strong></p>";
        
        if (count($productos) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>SKU</th><th>Código</th></tr>";
            foreach ($productos as $producto) {
                echo "<tr>";
                echo "<td>{$producto['id']}</td>";
                echo "<td>{$producto['nombre']}</td>";
                echo "<td>{$producto['precio']}</td>";
                echo "<td>{$producto['stock']}</td>";
                echo "<td>" . ($producto['sku'] ?? 'NULL') . "</td>";
                echo "<td>" . ($producto['codigo_barras'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error al usar POSController: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>❌ POSController.php NO existe en: $controllerPath</p>";
}

// Verificar ajax_handler
echo "<h4>Verificación del ajax_handler:</h4>";
$ajaxPath = '../ajax_handler.php';
if (file_exists($ajaxPath)) {
    echo "<p>✅ ajax_handler.php existe</p>";
} else {
    echo "<p>❌ ajax_handler.php NO existe en: $ajaxPath</p>";
}

echo "<hr>";
echo "<p><strong>Para probar con otros términos:</strong> <a href='?q=producto'>?q=producto</a> | <a href='?q=a'>?q=a</a> | <a href='?q=1'>?q=1</a></p>";
?>
