<?php
// Script de depuración para verificar datos de clientes
require 'includes/db.php';

try {
    echo "<h2>Verificación de datos de clientes</h2>";
    
    // Verificar estructura de la tabla
    echo "<h3>Estructura de la tabla clientes:</h3>";
    $stmt = $conn->query("DESCRIBE clientes");
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
    
    // Verificar datos de clientes
    echo "<h3>Últimos clientes registrados:</h3>";
    $stmt = $conn->query("SELECT id, rut_empresa, razon_social, direccion, telefono, correo, tipo_cliente, created_at FROM clientes ORDER BY id DESC LIMIT 10");
    echo "<table border='1'><tr><th>ID</th><th>RUT Empresa</th><th>Razón Social</th><th>Dirección</th><th>Teléfono</th><th>Correo</th><th>Tipo</th><th>Creado</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . ($row['rut_empresa'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['razon_social'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['direccion'] ?: '<strong style="color:red;">VACÍO</strong>') . "</td>";
        echo "<td>" . ($row['telefono'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['correo'] ?: 'N/A') . "</td>";
        echo "<td>{$row['tipo_cliente']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar ventas con clientes
    echo "<h3>Últimas ventas con datos de cliente:</h3>";
    $stmt = $conn->query("
        SELECT v.id, v.tipo_documento, v.total, 
               c.rut_empresa, c.razon_social, c.direccion, c.telefono, c.correo
        FROM ventas v 
        LEFT JOIN clientes c ON v.cliente_id = c.id 
        WHERE v.tipo_documento = 'factura'
        ORDER BY v.id DESC 
        LIMIT 5
    ");
    echo "<table border='1'><tr><th>Venta ID</th><th>Tipo</th><th>Total</th><th>RUT</th><th>Razón Social</th><th>Dirección</th><th>Teléfono</th><th>Correo</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['tipo_documento']}</td>";
        echo "<td>\${$row['total']}</td>";
        echo "<td>" . ($row['rut_empresa'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['razon_social'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['direccion'] ?: '<strong style="color:red;">VACÍO</strong>') . "</td>";
        echo "<td>" . ($row['telefono'] ?: 'N/A') . "</td>";
        echo "<td>" . ($row['correo'] ?: 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
