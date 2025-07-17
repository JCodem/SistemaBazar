<?php
namespace Modules\POS;

class POSModule {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function initialize() {
        // Registrar rutas, incluir scripts necesarios, etc.
        return $this;
    }
    
    public function renderPOS() {
        // Incluir la vista principal del POS
        include_once __DIR__ . '/views/pos.php';
    }
}
