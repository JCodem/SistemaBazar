-- =====================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS - SISTEMA BAZAR POS
-- =====================================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS sistema_bazar_pos;
USE sistema_bazar_pos;

-- =====================================================
-- TABLA 1: USUARIOS
-- =====================================================
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    rut VARCHAR(12) NOT NULL,
    rol ENUM('vendedor', 'jefe', 'admin') NOT NULL DEFAULT 'vendedor',
    contrasena VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA 2: PRODUCTOS
-- =====================================================
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(200) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    sku VARCHAR(50) UNIQUE NOT NULL,
    codigo_barras VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA 3: SESIONES_CAJA
-- =====================================================
CREATE TABLE sesiones_caja (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    fecha_apertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    monto_inicial DECIMAL(10,2) NOT NULL,
    monto_final DECIMAL(10,2) NULL,
    total_ventas DECIMAL(10,2) DEFAULT 0,
    estado ENUM('abierta', 'cerrada') DEFAULT 'abierta',
    observaciones TEXT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA 4: VENTAS
-- =====================================================
CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') NOT NULL,
    tipo_documento ENUM('boleta', 'factura') NOT NULL,
    numero_documento VARCHAR(50) UNIQUE NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    sesion_caja_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (sesion_caja_id) REFERENCES sesiones_caja(id) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA 5: VENTA_DETALLES
-- =====================================================
CREATE TABLE venta_detalles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA 6: ACTIVIDADES (OPCIONAL - PARA LOGS)
-- =====================================================
CREATE TABLE actividades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
);

-- =====================================================
-- TABLA 7: CONFIGURACION (OPCIONAL - PARA PARAMETROS DEL SISTEMA)
-- =====================================================
CREATE TABLE configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descripcion TEXT,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices para búsquedas frecuentes
CREATE INDEX idx_productos_sku ON productos(sku);
CREATE INDEX idx_productos_codigo_barras ON productos(codigo_barras);
CREATE INDEX idx_ventas_fecha ON ventas(fecha);
CREATE INDEX idx_ventas_usuario ON ventas(usuario_id);
CREATE INDEX idx_sesiones_caja_usuario ON sesiones_caja(usuario_id);
CREATE INDEX idx_sesiones_caja_estado ON sesiones_caja(estado);
CREATE INDEX idx_actividades_fecha ON actividades(fecha);
CREATE INDEX idx_actividades_usuario ON actividades(usuario_id);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, correo, rut, rol, contrasena) VALUES 
('Administrador', 'admin@sistemabazar.com', '11111111-1', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insertar configuraciones iniciales
INSERT INTO configuracion (clave, valor, descripcion) VALUES 
('numero_boleta', '1', 'Último número de boleta emitido'),
('numero_factura', '1', 'Último número de factura emitido'),
('nombre_empresa', 'Sistema Bazar POS', 'Nombre de la empresa'),
('rut_empresa', '76543210-K', 'RUT de la empresa'),
('direccion_empresa', 'Dirección de la empresa', 'Dirección física de la empresa'),
('telefono_empresa', '+56912345678', 'Teléfono de contacto'),
('email_empresa', 'contacto@sistemabazar.com', 'Email de contacto');

-- Insertar productos de ejemplo
INSERT INTO productos (nombre, precio, stock, sku, codigo_barras) VALUES 
('Coca Cola 350ml', 1500.00, 50, 'COCA350', '7501055301011'),
('Pan Hallulla', 800.00, 20, 'PAN001', '7806580000123'),
('Leche Entera 1L', 1200.00, 30, 'LECHE1L', '7804650000456'),
('Arroz 1kg', 1800.00, 25, 'ARROZ1K', '7809570000789');

-- =====================================================
-- TRIGGERS PARA AUTOMATIZACIÓN
-- =====================================================

-- Trigger para actualizar el stock cuando se registra una venta
DELIMITER $$
CREATE TRIGGER actualizar_stock_venta 
AFTER INSERT ON venta_detalles
FOR EACH ROW
BEGIN
    UPDATE productos 
    SET stock = stock - NEW.cantidad 
    WHERE id = NEW.producto_id;
END$$

-- Trigger para actualizar el total de ventas en sesión de caja
CREATE TRIGGER actualizar_total_sesion 
AFTER INSERT ON ventas
FOR EACH ROW
BEGIN
    UPDATE sesiones_caja 
    SET total_ventas = total_ventas + NEW.total 
    WHERE id = NEW.sesion_caja_id;
END$$

-- Trigger para registrar actividad cuando se crea una venta
CREATE TRIGGER log_nueva_venta 
AFTER INSERT ON ventas
FOR EACH ROW
BEGIN
    INSERT INTO actividades (usuario_id, accion, descripcion) 
    VALUES (NEW.usuario_id, 'VENTA_CREADA', 
            CONCAT('Venta creada con ID: ', NEW.id, ', Total: $', NEW.total));
END$$

DELIMITER ;

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================

/*
NOTAS IMPORTANTES:
- La contraseña del usuario admin por defecto es: password (hasheada con bcrypt)
- Se recomienda cambiar esta contraseña inmediatamente después de la instalación
- Los triggers automatizan la actualización de stock y totales
- Los índices mejoran el rendimiento de las consultas frecuentes
- La tabla configuracion permite parametrizar el sistema
- Las foreign keys mantienen la integridad referencial
- Se usan RESTRICT en lugar de CASCADE para evitar eliminaciones accidentales
*/

-- Verificar que todas las tablas se crearon correctamente
SHOW TABLES;

-- Mostrar la estructura de cada tabla
DESCRIBE usuarios;
DESCRIBE productos;
DESCRIBE sesiones_caja;
DESCRIBE ventas;
DESCRIBE venta_detalles;
DESCRIBE actividades;
DESCRIBE configuracion;
