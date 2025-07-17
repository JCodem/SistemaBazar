<?php
require_once '../../includes/middleware_unificado.php';
middlewareAmbos(); // Permite tanto vendedores como jefes

// Incluir archivos necesarios
require_once '../../includes/db.php'; // Este archivo crea $conn directamente
require_once '../../modules/pos/POSModule.php';

use Modules\POS\POSModule;

// Usar la conexión existente ($conn) creada en db.php
// No necesitamos crear una nueva instancia de Database

// Inicializar el módulo POS con la conexión existente
$posModule = new POSModule($conn);
$posModule->initialize();

// Mostrar la interfaz del POS
$posModule->renderPOS();
?>
