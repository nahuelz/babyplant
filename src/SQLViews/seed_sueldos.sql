-- Catálogos auxiliares (idempotentes, no fallan si ya existen)

-- Categorías
INSERT INTO babyplant.categoria (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Empleado', 'Categoría empleado', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.categoria WHERE nombre = 'Empleado');

-- Obras sociales
INSERT INTO babyplant.obra_social (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Sin obra social', 'Sin obra social', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.obra_social WHERE nombre = 'Sin obra social');

-- Bancos
INSERT INTO babyplant.banco (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Banco Nación', 'Banco de la Nación Argentina', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.banco WHERE nombre = 'Banco Nación');

-- Modalidades de pago
INSERT INTO babyplant.tipo_modalidad_pago (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Semanal', 'Pago semanal', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_modalidad_pago WHERE codigo_interno = 1);

INSERT INTO babyplant.tipo_modalidad_pago (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Mensual', 'Pago mensual', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_modalidad_pago WHERE codigo_interno = 2);

-- Estados de liquidación
INSERT INTO babyplant.estado_liquidacion (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Borrador', 'Liquidación en edición', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.estado_liquidacion WHERE codigo_interno = 1);

INSERT INTO babyplant.estado_liquidacion (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Calculada', 'Liquidación calculada', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.estado_liquidacion WHERE codigo_interno = 2);

INSERT INTO babyplant.estado_liquidacion (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Aprobada', 'Liquidación aprobada', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.estado_liquidacion WHERE codigo_interno = 3);

INSERT INTO babyplant.estado_liquidacion (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Pagada', 'Liquidación pagada', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.estado_liquidacion WHERE codigo_interno = 4);

INSERT INTO babyplant.estado_liquidacion (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Anulada', 'Liquidación anulada', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.estado_liquidacion WHERE codigo_interno = 5);

-- Tipos de concepto de liquidación
INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Adelanto', 'Adelantos imputados al empleado', 1, 1, 'DESCUENTO'
WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 1);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Hora extra', 'Horas extra trabajadas', 2, 1, 'INGRESO'
WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 2);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Feriado', 'Días feriados trabajados', 3, 1, 'INGRESO'
WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 3);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Guardia', 'Guardias', 4, 1, 'INGRESO'
WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 4);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Presentismo', 'Bono por presentismo', 5, 1, 'DESCUENTO'
WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 5);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'SAC', 'Sueldo Anual Complementario', 6, 1, 'INGRESO'
    WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 6);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Prestamo', 'Prestamo', 7, 1, 'DESCUENTO'
    WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 7);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Vacaciones', 'Vacaciones', 8, 1, 'INGRESO'
    WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 8);

INSERT INTO babyplant.tipo_concepto_liquidacion (nombre, descripcion, codigo_interno, habilitado, tipo)
SELECT 'Dif. Aumento', 'Diferencia aumento', 8, 1, 'INGRESO'
    WHERE NOT EXISTS (SELECT 1 FROM babyplant.tipo_concepto_liquidacion WHERE codigo_interno = 9);

-- Modos de pago (para pagos a empleados, si no existen)
INSERT INTO babyplant.modo_pago (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Efectivo', NULL, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.modo_pago WHERE nombre = 'Efectivo');

INSERT INTO babyplant.modo_pago (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Transferencia', NULL, 2, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.modo_pago WHERE nombre = 'Transferencia');

INSERT INTO babyplant.modo_pago (nombre, descripcion, codigo_interno, habilitado)
SELECT 'Cheque', NULL, 3, 1
WHERE NOT EXISTS (SELECT 1 FROM babyplant.modo_pago WHERE nombre = 'Cheque');
