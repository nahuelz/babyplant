-- MIGRACIÓN PARA EL SISTEMA DE REVENTA
-- Ejecutar en orden para crear tablas, columnas y datos de estados

-- 1. AGREGAR COLUMNA es_reventa A LA TABLA entrega_producto
ALTER TABLE entrega_producto
ADD COLUMN es_reventa TINYINT(1) NOT NULL DEFAULT 0 AFTER estado_id_estado_entrega_producto;

-- 2. CREAR TABLA estado_reventa
CREATE TABLE estado_reventa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(500),
    codigo_interno INT,
    color VARCHAR(255),
    icono VARCHAR(255),
    color_icono VARCHAR(255),
    class_name VARCHAR(255),
    id_usuario_creacion INT,
    id_usuario_ultima_modificacion INT,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_modificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_baja DATETIME,
    habilitado TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY unique_nombre_estado_reventa (nombre, fecha_baja)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. INSERTAR ESTADOS DE REVENTA
INSERT INTO estado_reventa (id, nombre, descripcion, codigo_interno, color, icono, color_icono, class_name, id_usuario_creacion) VALUES
(1, 'Pendiente de Entrega', 'Reventa creada, pendiente de materializar la entrega', 1, 'warning', 'fa fa-clock', 'warning', 'badge-warning', 1),
(2, 'Entregada', 'Reventa entregada al cliente comprador', 2, 'success', 'fa fa-check', 'success', 'badge-success', 1),
(3, 'Cancelada', 'Reventa cancelada, bandejas liberadas para reventa', 3, 'danger', 'fa fa-ban', 'danger', 'badge-danger', 1);

-- 4. CREAR TABLA estado_reventa_historico
CREATE TABLE estado_reventa_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reventa INT,
    id_estado_reventa INT NOT NULL,
    fecha DATETIME NOT NULL,
    motivo VARCHAR(255),
    id_usuario_creacion INT,
    id_usuario_ultima_modificacion INT,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_modificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_baja DATETIME,
    FOREIGN KEY (id_reventa) REFERENCES reventa(id) ON DELETE SET NULL,
    FOREIGN KEY (id_estado_reventa) REFERENCES estado_reventa(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CREAR TABLA reventa
CREATE TABLE reventa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_devolucion INT NOT NULL,
    id_cliente INT NOT NULL,
    cantidad_bandejas DECIMAL(6,1) NOT NULL,
    precio_unitario DECIMAL(10,2),
    precio_unitario_original DECIMAL(10,2),
    id_entrega_producto INT,
    fecha_reventa DATETIME,
    observacion VARCHAR(255),
    id_estado_reventa INT NOT NULL,
    id_usuario_creacion INT,
    id_usuario_ultima_modificacion INT,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_modificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    fecha_baja DATETIME,
    FOREIGN KEY (id_devolucion) REFERENCES devolucion(id),
    FOREIGN KEY (id_cliente) REFERENCES usuario(id),
    FOREIGN KEY (id_entrega_producto) REFERENCES entrega_producto(id) ON DELETE SET NULL,
    FOREIGN KEY (id_estado_reventa) REFERENCES estado_reventa(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. INSERTAR NUEVOS ESTADOS DE DEVOLUCIÓN
-- Verificar primero si ya existen para evitar duplicados
INSERT IGNORE INTO estado_devolucion (id, nombre, descripcion, codigo_interno, color, icono, color_icono, class_name, id_usuario_creacion) VALUES
(2, 'Revendida Parcial', 'Parte de las bandejas devueltas fueron revendidas', 2, 'info', 'fa fa-exchange-alt', 'info', 'badge-info', 1),
(3, 'Revendida', 'Todas las bandejas devueltas fueron revendidas', 3, 'success', 'fa fa-check-circle', 'success', 'badge-success', 1),
(4, 'Descartada', 'Las bandejas restantes fueron descartadas, no disponibles para reventa', 4, 'danger', 'fa fa-trash-alt', 'danger', 'badge-danger', 1);

-- 7. AGREGAR RELACIÓN INVERSA EN Devolucion (opcional, para navegación ORM)
-- Esto se maneja a nivel de entidad Doctrine, no requiere SQL adicional
