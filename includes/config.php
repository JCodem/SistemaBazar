<?php
/**
 * Archivo de Configuración Principal
 *
 * Define constantes globales y configuraciones iniciales para la aplicación.
 */

// Definir BASE_PATH como la ruta absoluta al directorio raíz del proyecto.
// Esto permite tener rutas consistentes en toda la aplicación.
define('BASE_PATH', dirname(__DIR__));

// Iniciar la sesión si no está ya activa.
// Es fundamental para el manejo de la autenticación y los datos del usuario.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aquí se pueden añadir otras configuraciones globales en el futuro,
// como configuraciones de zona horaria, codificación de caracteres, etc.
// date_default_timezone_set('America/Santiago');
// mb_internal_encoding('UTF-8');

?>
