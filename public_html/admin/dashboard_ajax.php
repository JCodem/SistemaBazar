<?php
require_once '../../includes/middleware_unificado.php';
require_once '../../includes/db.php';

// Verificar que es administrador
middlewareAdmin();

header('Content-Type: application/json');

if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no especificada']);
    exit;
}

$action = $_GET['action'];

switch ($action) {
    case 'get_stats':
        $stats = obtenerEstadisticasActualizadas($conn);
        echo json_encode($stats);
        break;
        
    case 'get_recent_sales':
        $sales = obtenerVentasRecientes($conn);
        echo json_encode($sales);
        break;
        
    case 'get_low_stock':
        $products = obtenerProductosStockBajo($conn);
        echo json_encode($products);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Acción no encontrada']);
        break;
}

function obtenerEstadisticasActualizadas($conn) {
    $stats = [];
    
    try {
        // Total ventas del día
        $query = "SELECT COALESCE(SUM(total), 0) as total_hoy FROM ventas WHERE DATE(fecha) = CURDATE()";
        $result = $conn->query($query);
        $stats['ventas_hoy'] = $result ? $result->fetch_assoc()['total_hoy'] : 0;
        
        // Ventas de ayer para comparación
        $query = "SELECT COALESCE(SUM(total), 0) as total_ayer FROM ventas WHERE DATE(fecha) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $result = $conn->query($query);
        $ventas_ayer = $result ? $result->fetch_assoc()['total_ayer'] : 0;
        
        // Calcular porcentaje de cambio
        if ($ventas_ayer > 0) {
            $stats['cambio_ventas'] = (($stats['ventas_hoy'] - $ventas_ayer) / $ventas_ayer) * 100;
        } else {
            $stats['cambio_ventas'] = $stats['ventas_hoy'] > 0 ? 100 : 0;
        }
        
        // Vendedores activos
        $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol IN ('vendedor', 'jefe')";
        $result = $conn->query($query);
        $stats['vendedores'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Productos en stock
        $query = "SELECT COUNT(*) as total FROM productos WHERE stock > 0";
        $result = $conn->query($query);
        $stats['productos_stock'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Transacciones del día
        $query = "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = CURDATE()";
        $result = $conn->query($query);
        $stats['transacciones_hoy'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Transacciones de ayer
        $query = "SELECT COUNT(*) as total FROM ventas WHERE DATE(fecha) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $result = $conn->query($query);
        $transacciones_ayer = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Cambio en transacciones
        if ($transacciones_ayer > 0) {
            $stats['cambio_transacciones'] = (($stats['transacciones_hoy'] - $transacciones_ayer) / $transacciones_ayer) * 100;
        } else {
            $stats['cambio_transacciones'] = $stats['transacciones_hoy'] > 0 ? 100 : 0;
        }
        
        // Productos con stock bajo
        $query = "SELECT COUNT(*) as total FROM productos WHERE stock <= 5 AND stock > 0";
        $result = $conn->query($query);
        $stats['stock_bajo'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Total productos
        $query = "SELECT COUNT(*) as total FROM productos";
        $result = $conn->query($query);
        $stats['total_productos'] = $result ? $result->fetch_assoc()['total'] : 0;
        
        // Ventas por hora del día actual
        $query = "SELECT HOUR(fecha) as hora, COUNT(*) as cantidad, SUM(total) as monto
                  FROM ventas 
                  WHERE DATE(fecha) = CURDATE() 
                  GROUP BY HOUR(fecha) 
                  ORDER BY hora";
        $result = $conn->query($query);
        $stats['ventas_por_hora'] = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['ventas_por_hora'][] = $row;
            }
        }
        
        $stats['success'] = true;
        $stats['timestamp'] = date('Y-m-d H:i:s');
        
    } catch (Exception $e) {
        $stats['error'] = 'Error al obtener estadísticas: ' . $e->getMessage();
        $stats['success'] = false;
    }
    
    return $stats;
}

function obtenerVentasRecientes($conn, $limit = 10) {
    $ventas = [];
    
    try {
        $query = "SELECT v.id, v.fecha, v.total, v.metodo_pago, u.nombre as vendedor
                  FROM ventas v
                  JOIN usuarios u ON v.usuario_id = u.id
                  ORDER BY v.fecha DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $ventas[] = $row;
        }
        
    } catch (Exception $e) {
        return ['error' => 'Error al obtener ventas recientes: ' . $e->getMessage()];
    }
    
    return $ventas;
}

function obtenerProductosStockBajo($conn, $limite_stock = 5) {
    $productos = [];
    
    try {
        $query = "SELECT id, nombre, stock, precio
                  FROM productos 
                  WHERE stock <= ? AND stock > 0
                  ORDER BY stock ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $limite_stock);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        
    } catch (Exception $e) {
        return ['error' => 'Error al obtener productos con stock bajo: ' . $e->getMessage()];
    }
    
    return $productos;
}
?>
