<?php
/**
 * Funciones helper para facilitar la migración de MySQLi a PDO
 */

/**
 * Ejecutar una consulta simple y retornar resultados
 */
function queryPDO($conn, $sql, $params = []) {
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        throw new Exception("Error en consulta: " . $e->getMessage());
    }
}

/**
 * Obtener un solo resultado
 */
function fetchOnePDO($conn, $sql, $params = []) {
    $stmt = queryPDO($conn, $sql, $params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Obtener todos los resultados
 */
function fetchAllPDO($conn, $sql, $params = []) {
    $stmt = queryPDO($conn, $sql, $params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ejecutar una consulta de inserción/actualización/eliminación
 */
function executePDO($conn, $sql, $params = []) {
    $stmt = queryPDO($conn, $sql, $params);
    return $stmt->rowCount();
}

/**
 * Obtener el último ID insertado
 */
function lastInsertIdPDO($conn) {
    return $conn->lastInsertId();
}
?>
