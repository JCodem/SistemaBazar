<?php
require_once '../../includes/middleware_unificado.php';
middlewareAmbos(); // Permite tanto vendedores como jefes

// Incluir archivos necesarios
require_once '../../includes/db.php';
require_once 'POSModule.php';

use Modules\POS\POSModule;

// Usar la conexión existente ($conn) creada en db.php

// Inicializar el módulo POS
$posModule = new POSModule($conn);
$posModule->initialize();

// Mostrar la interfaz del POS
$posModule->renderPOS();
?>
