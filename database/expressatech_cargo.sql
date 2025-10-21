-- =====================================================
-- EXPRESSATECH CARGO - BASE DE DATOS COMPLETA
-- Sistema de Gestión Logística Miami - Venezuela
-- Versión: 1.0
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =====================================================
-- TABLA 1: USUARIOS
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'admin') DEFAULT 'cliente',
    margen_personalizado DECIMAL(5,2) DEFAULT NULL COMMENT 'Margen VIP personalizado (%)',
    tipo_cliente ENUM('Normal', 'VIP', 'Premium') DEFAULT 'Normal',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA 2: CATÁLOGO 4LIFE (74 productos)
-- =====================================================
CREATE TABLE IF NOT EXISTS catalogo_4life (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_producto VARCHAR(200) UNIQUE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    orden INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserción de los 74 productos 4Life
INSERT INTO catalogo_4life (nombre_producto, orden) VALUES
('Transfer Factor Plus', 1),
('Transfer Factor Tri-Factor', 2),
('Transfer Factor Chewable', 3),
('Transfer Factor Classic', 4),
('Immune Spray', 5),
('RenewAll', 6),
('Immune Boost', 7),
('AgePro', 8),
('Cardio', 9),
('Colágeno', 10),
('SleepRite', 11),
('ReCall', 12),
('Lung', 13),
('KBU', 14),
('Vista', 15),
('Metabolite', 16),
('Glucoach', 17),
('Glutamine Prime', 18),
('Belle Vie', 19),
('MalePro', 20),
('RioVida Líquido', 21),
('RioVida Stix', 22),
('RioVida Chews', 23),
('RioVida Burst', 24),
('RiteStart Mujer', 25),
('RiteStart Hombre', 26),
('RiteStart Niños y Adolescentes', 27),
('NutraStart Blue Vanilla', 28),
('Sistema de Restauración Digest', 29),
('Pre/o Biotics', 30),
('Aloe Vera Stix', 31),
('Super Detox', 32),
('Fibre System Plus', 33),
('Tea4Life', 34),
('PhytoLax', 35),
('Digestive Enzymes', 36),
('Pro-TF', 37),
('PreZoom', 38),
('Protein Bar', 39),
('ShapeRite', 40),
('Burn Mujer', 41),
('Burn Hombre', 42),
('Paquete 4LifeTransform Get Burning* (Hombre o Mujer)', 43),
('Paquete 4LifeTransform Lean and Fit* (Mujer)', 44),
('Paquete 4LifeTransform Shred* (Hombre)', 45),
('Energy Go Stix (30 sobres)', 46),
('Energy Go Stix (15 sobres)', 47),
('Gold Factor', 48),
('Zinc Factor', 49),
('Limpiador de Aceite a Espuma', 50),
('Mascarilla de Barro Volcánico', 51),
('Protector Solar Humectante con FPS 30', 52),
('Tónico Cuatro en Uno', 53),
('Esencia de Vitaminas', 54),
('Crema de Ojos', 55),
('Crema Humectante', 56),
('Mascarilla de Velo', 57),
('äKwä Sistema de Cuidado de la Piel', 58),
('enummi Loción Corporal', 59),
('enummi Pasta Dental', 60),
('enummi Champú', 61),
('enummi Acondicionador', 62),
('Menupause Support', 63),
('Cal-Mag Complex', 64),
('Essential Fatty Acid Complex', 65),
('Fibro AMJ Fórmula Diurna', 66),
('Flex4Life', 67),
('Fortified Colostrum', 68),
('Gurmar', 69),
('Life C Chewable', 70),
('MusculoSkeletal Formula', 71),
('Multiplex', 72),
('Stress Formula', 73),
('4Life Fortify', 74);

-- =====================================================
-- TABLA 3: CONSOLIDADOS
-- =====================================================
CREATE TABLE IF NOT EXISTS consolidados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_consolidado VARCHAR(50) UNIQUE NOT NULL,
    costo_courier DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    costo_recoleccion DECIMAL(10,2) DEFAULT 0.00,
    costo_logistica DECIMAL(10,2) DEFAULT 0.00,
    costo_manejo DECIMAL(10,2) DEFAULT 0.00,
    costo_total DECIMAL(10,2) GENERATED ALWAYS AS (costo_courier + costo_recoleccion + costo_logistica + costo_manejo) STORED,
    total_productos INT DEFAULT 0,
    costo_por_producto DECIMAL(10,4) DEFAULT 0.00,
    margen_ganancia DECIMAL(5,2) DEFAULT 30.00 COMMENT 'Margen base del consolidado (%)',
    precio_venta_producto DECIMAL(10,4) DEFAULT 0.00,
    total_facturado DECIMAL(10,2) DEFAULT 0.00,
    total_pagado DECIMAL(10,2) DEFAULT 0.00,
    ganancia_real DECIMAL(10,2) GENERATED ALWAYS AS (total_pagado - costo_total) STORED,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_salida_miami DATETIME,
    estado ENUM('Abierto', 'Cerrado', 'En Tránsito', 'Entregado') DEFAULT 'Abierto',
    notas TEXT,
    INDEX idx_numero (numero_consolidado),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA 4: ENVÍOS
-- =====================================================
CREATE TABLE IF NOT EXISTS envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT DEFAULT NULL COMMENT 'NULL si es envío del admin sin cliente',
    consolidado_id INT DEFAULT NULL,
    
    -- Datos del envío
    fecha_compra DATE NOT NULL,
    tracking_original VARCHAR(100),
    empresa_compra VARCHAR(100) NOT NULL,
    factura_url VARCHAR(255),
    tracking_interno VARCHAR(50) UNIQUE,
    
    -- Destinatario (para envíos a nombre de terceros)
    destinatario_nombre VARCHAR(200) COMMENT 'Nombre de quien recibe (si es diferente al titular)',
    
    -- Estados del envío
    estado ENUM(
        'En tránsito',
        'Recibido en Miami',
        'Consolidado',
        'En camino a Venezuela',
        'Llegada a Aduana',
        'Llegada a Puerto Ordaz',
        'Pendiente por retiro',
        'Entregado'
    ) DEFAULT 'En tránsito',
    
    -- Costos y pagos
    costo_calculado DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Costo asignado después de consolidar',
    margen_aplicado DECIMAL(5,2) COMMENT 'Margen usado en el cálculo (histórico)',
    saldo_pendiente DECIMAL(10,2) DEFAULT 0.00,
    
    -- Entrega final
    direccion_final TEXT,
    persona_retira VARCHAR(200),
    empresa_reenvio ENUM('MRW', 'ZOOM', 'Tealca', 'Retiro en sede') DEFAULT NULL,
    ciudad_destino VARCHAR(100),
    
    -- Marcadores especiales
    es_envio_admin BOOLEAN DEFAULT FALSE COMMENT 'TRUE si lo creó el admin (para sí mismo o cliente)',
    es_costo_cero BOOLEAN DEFAULT FALSE COMMENT 'TRUE para envíos sin costo',
    
    -- Fechas del proceso
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_llegada_miami DATETIME,
    fecha_consolidacion DATETIME,
    fecha_salida_miami DATETIME,
    fecha_llegada_aduana DATETIME,
    fecha_llegada_puerto_ordaz DATETIME,
    fecha_entrega_final DATETIME,
    
    notas_admin TEXT,
    
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (consolidado_id) REFERENCES consolidados(id) ON DELETE SET NULL,
    INDEX idx_cliente (cliente_id),
    INDEX idx_consolidado (consolidado_id),
    INDEX idx_tracking (tracking_interno),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA 5: PRODUCTOS
-- =====================================================
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    envio_id INT NOT NULL,
    nombre_producto VARCHAR(200) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    detalle TEXT COMMENT 'Comentario o descripción adicional',
    precio_unitario DECIMAL(10,4) DEFAULT 0.00 COMMENT 'Precio calculado por unidad',
    subtotal DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
    FOREIGN KEY (envio_id) REFERENCES envios(id) ON DELETE CASCADE,
    INDEX idx_envio (envio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA 6: PAGOS
-- =====================================================
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    envio_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo ENUM('Zelle', 'Zinli', 'Dólares Banesco', 'Binance USDT', 'Bolívares') NOT NULL,
    tasa_binance DECIMAL(10,4) COMMENT 'Tasa del día si es pago en Bolívares',
    referencia VARCHAR(100) COMMENT 'Número de referencia del pago',
    comprobante_url VARCHAR(255),
    estado ENUM('Pendiente', 'Verificado', 'Rechazado') DEFAULT 'Pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_verificacion DATETIME,
    verificado_por INT COMMENT 'ID del admin que verificó',
    notas_verificacion TEXT,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (envio_id) REFERENCES envios(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_envio (envio_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA 7: NOTIFICACIONES (Log de emails)
-- =====================================================
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    envio_id INT DEFAULT NULL,
    tipo VARCHAR(100) NOT NULL COMMENT 'confirmacion_registro, llegada_miami, costo_asignado, etc.',
    asunto VARCHAR(255),
    mensaje TEXT,
    email_destino VARCHAR(150),
    enviado BOOLEAN DEFAULT FALSE,
    fecha_programada DATETIME,
    fecha_envio DATETIME,
    error TEXT COMMENT 'Mensaje de error si falla el envío',
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (envio_id) REFERENCES envios(id) ON DELETE SET NULL,
    INDEX idx_cliente (cliente_id),
    INDEX idx_tipo (tipo),
    INDEX idx_enviado (enviado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA 8: RECORDATORIOS DE PAGO
-- =====================================================
CREATE TABLE IF NOT EXISTS recordatorios_pago (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    monto_total_pendiente DECIMAL(10,2) DEFAULT 0.00,
    cantidad_envios_pendientes INT DEFAULT 0,
    ultimo_recordatorio DATETIME,
    proximo_recordatorio DATETIME,
    cantidad_recordatorios INT DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (cliente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_proximo (proximo_recordatorio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR USUARIO ADMINISTRADOR
-- =====================================================
-- Password: Admin123! (debes cambiarlo después)
INSERT INTO usuarios (nombre, apellido, email, telefono, password, tipo, tipo_cliente) VALUES
('Gustavo', 'Tomasi', 'contacto@expressatech.net', '', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Premium');

-- =====================================================
-- VISTAS ÚTILES PARA REPORTES
-- =====================================================

-- Vista: Resumen de clientes con deuda
CREATE OR REPLACE VIEW vista_clientes_morosos AS
SELECT 
    u.id,
    u.nombre,
    u.apellido,
    u.email,
    u.telefono,
    COUNT(e.id) as envios_pendientes,
    SUM(e.saldo_pendiente) as total_deuda,
    MIN(e.fecha_registro) as envio_mas_antiguo,
    DATEDIFF(NOW(), MIN(e.fecha_registro)) as dias_sin_pagar
FROM usuarios u
INNER JOIN envios e ON u.id = e.cliente_id
WHERE e.saldo_pendiente > 0
GROUP BY u.id
HAVING total_deuda > 0
ORDER BY dias_sin_pagar DESC;

-- Vista: Resumen de consolidados
CREATE OR REPLACE VIEW vista_resumen_consolidados AS
SELECT 
    c.id,
    c.numero_consolidado,
    c.costo_total,
    c.total_productos,
    c.margen_ganancia,
    c.precio_venta_producto,
    COUNT(DISTINCT e.cliente_id) as total_clientes,
    COUNT(e.id) as total_envios,
    c.total_facturado,
    c.total_pagado,
    (c.total_facturado - c.total_pagado) as pendiente_cobro,
    c.ganancia_real,
    c.estado,
    c.fecha_creacion
FROM consolidados c
LEFT JOIN envios e ON c.id = e.consolidado_id
GROUP BY c.id
ORDER BY c.fecha_creacion DESC;

-- Vista: Top productos más enviados
CREATE OR REPLACE VIEW vista_productos_top AS
SELECT 
    p.nombre_producto,
    SUM(p.cantidad) as total_unidades,
    COUNT(DISTINCT p.envio_id) as total_envios,
    COUNT(DISTINCT e.cliente_id) as total_clientes
FROM productos p
INNER JOIN envios e ON p.envio_id = e.id
GROUP BY p.nombre_producto
ORDER BY total_unidades DESC
LIMIT 20;

-- =====================================================
-- TRIGGERS AUTOMÁTICOS
-- =====================================================

-- Trigger: Generar tracking interno automáticamente
DELIMITER $$
CREATE TRIGGER generar_tracking_interno
BEFORE INSERT ON envios
FOR EACH ROW
BEGIN
    IF NEW.tracking_interno IS NULL THEN
        SET NEW.tracking_interno = CONCAT('EXP-', YEAR(NOW()), LPAD(MONTH(NOW()), 2, '0'), '-', LPAD((SELECT COALESCE(MAX(id), 0) + 1 FROM envios), 5, '0'));
    END IF;
END$$
DELIMITER ;

-- Trigger: Actualizar saldo pendiente al asignar costo
DELIMITER $$
CREATE TRIGGER actualizar_saldo_pendiente
BEFORE UPDATE ON envios
FOR EACH ROW
BEGIN
    IF NEW.costo_calculado != OLD.costo_calculado THEN
        SET NEW.saldo_pendiente = NEW.costo_calculado - (
            SELECT COALESCE(SUM(monto), 0) 
            FROM pagos 
            WHERE envio_id = NEW.id AND estado = 'Verificado'
        );
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

-- Procedimiento: Calcular costos de un consolidado
DELIMITER $$
CREATE PROCEDURE calcular_costos_consolidado(IN consolidado_id_param INT)
BEGIN
    DECLARE total_prods INT;
    DECLARE costo_unit DECIMAL(10,4);
    DECLARE margen DECIMAL(5,2);
    DECLARE precio_venta DECIMAL(10,4);
    
    -- Contar productos en el consolidado
    SELECT COUNT(*) INTO total_prods
    FROM productos p
    INNER JOIN envios e ON p.envio_id = e.id
    WHERE e.consolidado_id = consolidado_id_param;
    
    -- Obtener datos del consolidado
    SELECT 
        (costo_total / total_prods),
        margen_ganancia
    INTO costo_unit, margen
    FROM consolidados
    WHERE id = consolidado_id_param;
    
    -- Calcular precio de venta
    SET precio_venta = costo_unit * (1 + (margen / 100));
    
    -- Actualizar consolidado
    UPDATE consolidados
    SET total_productos = total_prods,
        costo_por_producto = costo_unit,
        precio_venta_producto = precio_venta
    WHERE id = consolidado_id_param;
    
    -- Actualizar precios de productos
    UPDATE productos p
    INNER JOIN envios e ON p.envio_id = e.id
    SET p.precio_unitario = precio_venta
    WHERE e.consolidado_id = consolidado_id_param;
    
END$$
DELIMITER ;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================