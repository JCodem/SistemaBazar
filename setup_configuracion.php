<?php
// Script para verificar tabla configuración
require 'includes/db.php';

try {
    // Verificar si existe la tabla configuracion
    $stmt = $conn->query("SHOW TABLES LIKE 'configuracion'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Tabla 'configuracion' no existe. Creándola...<br>";
        
        // Crear tabla configuracion
        $conn->exec("
            CREATE TABLE configuracion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(100) NOT NULL UNIQUE,
                valor TEXT NOT NULL,
                descripcion TEXT,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Insertar valores por defecto
        $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)");
        $stmt->execute(['iva_porcentaje', '19', 'Porcentaje de IVA aplicado a las ventas']);
        $stmt->execute(['empresa_nombre', 'Sistema Bazar', 'Nombre de la empresa']);
        $stmt->execute(['empresa_direccion', 'Santiago, Chile', 'Dirección de la empresa']);
        
        echo "✅ Tabla 'configuracion' creada con valores por defecto<br>";
    } else {
        echo "✅ Tabla 'configuracion' existe<br>";
    }
    
    // Mostrar configuración actual
    echo "<h3>Configuración actual:</h3>";
    $stmt = $conn->query("SELECT * FROM configuracion ORDER BY clave");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Clave</th><th>Valor</th><th>Descripción</th><th>Última actualización</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['clave']}</td>";
        echo "<td>{$row['valor']}</td>";
        echo "<td>{$row['descripcion']}</td>";
        echo "<td>{$row['fecha_actualizacion']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar si existe el IVA
    $stmt = $conn->prepare("SELECT valor FROM configuracion WHERE clave = 'iva_porcentaje'");
    $stmt->execute();
    $iva = $stmt->fetchColumn();
    
    if ($iva) {
        echo "<br>✅ IVA configurado: {$iva}%";
    } else {
        echo "<br>❌ IVA no configurado. Agregándolo...";
        $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES ('iva_porcentaje', '19', 'Porcentaje de IVA aplicado a las ventas')");
        $stmt->execute();
        echo "<br>✅ IVA agregado: 19%";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
