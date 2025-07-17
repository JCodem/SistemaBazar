<?php
/**
 * Middleware de Autenticación y Autorización Unificado
 * Maneja la verificación de sesión y roles para todo el sistema
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 */
function verificarAutenticacion() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol'])) {
        return false;
    }
    return true;
}

/**
 * Verificar si el usuario tiene el rol requerido
 * @param string|array $rolesPermitidos - Rol o array de roles permitidos
 * @return bool
 */
function verificarRol($rolesPermitidos) {
    if (!verificarAutenticacion()) {
        return false;
    }
    
    // Convertir a array si es un string
    if (is_string($rolesPermitidos)) {
        $rolesPermitidos = [$rolesPermitidos];
    }
    
    return in_array($_SESSION['user_rol'], $rolesPermitidos);
}

/**
 * Middleware principal - Verificar autenticación y rol
 * @param string|array $rolesRequeridos - Rol o roles permitidos para acceder
 * @param string $redirigirA - URL de redirección en caso de fallo (opcional)
 */
function middleware($rolesRequeridos = null, $redirigirA = null) {
    // Verificar autenticación básica
    if (!verificarAutenticacion()) {
        $redirigirA = $redirigirA ?: '../login.php?error=no_autenticado';
        header("Location: $redirigirA");
        exit;
    }
    
    // Si no se especifican roles, solo verificar autenticación
    if ($rolesRequeridos === null) {
        return true;
    }
    
    // Verificar rol específico
    if (!verificarRol($rolesRequeridos)) {
        // Determinar redirección según el rol actual
        $rolActual = $_SESSION['user_rol'];
        
        if ($redirigirA === null) {
            // Redirección automática según rol
            if ($rolActual === 'jefe') {
                $redirigirA = '../admin/dashboard.php?error=acceso_denegado';
            } elseif ($rolActual === 'vendedor') {
                $redirigirA = '../vendedor/dashboard.php?error=acceso_denegado';
            } else {
                $redirigirA = '../login.php?error=rol_invalido';
            }
        }
        
        header("Location: $redirigirA");
        exit;
    }
    
    return true;
}

/**
 * Middleware específico para solo administradores (jefe)
 */
function middlewareAdmin($redirigirA = null) {
    return middleware('jefe', $redirigirA);
}

/**
 * Middleware específico para solo vendedores
 */
function middlewareVendedor($redirigirA = null) {
    // Verificar primero que sea un vendedor
    $resultado = middleware('vendedor', $redirigirA);
    
    
    return $resultado;
}

/**
 * Middleware para ambos roles (jefe y vendedor)
 */
function middlewareAmbos($redirigirA = null) {
    return middleware(['jefe', 'vendedor'], $redirigirA);
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    if (!verificarAutenticacion()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nombre' => $_SESSION['user_nombre'] ?? 'Usuario',
        'rol' => $_SESSION['user_rol']
    ];
}

/**
 * Verificar si el usuario actual es admin
 */
function esAdmin() {
    return verificarRol('jefe');
}

/**
 * Verificar si el usuario actual es vendedor
 */
function esVendedor() {
    return verificarRol('vendedor');
}

// Auto-ejecutar middleware si se incluye directamente con parámetros GET
if (isset($_GET['middleware'])) {
    $rolRequerido = $_GET['middleware'];
    $redirigir = $_GET['redirect'] ?? null;
    
    if ($rolRequerido === 'admin') {
        middlewareAdmin($redirigir);
    } elseif ($rolRequerido === 'vendedor') {
        middlewareVendedor($redirigir);
    } elseif ($rolRequerido === 'ambos') {
        middlewareAmbos($redirigir);
    } else {
        middleware($rolRequerido, $redirigir);
    }
}
?>
