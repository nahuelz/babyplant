CREATE DEFINER = root@`%` VIEW view_planificacion AS
SELECT
    pp.id                                                               AS id,
    tb.color                                                            AS colorBandeja,
    CONCAT(pp.id, ' - ', tv.nombre,
           ' (', pp.cantidad_bandejas_pedidas, ' x', tb.nombre, ')')    AS title,
    CONCAT(epp.class_name, ' ', tp.nombre, ' ', epp.nombre)             AS className,
    pp.fecha_siembra_planificacion                                      AS fechaSiembraPlanificacion,
    CONCAT(tp.nombre,' ',tsp.nombre,' ',tv.nombre,' (x',tb.nombre,')')  AS producto,
    tp.nombre                                                           AS tipoProducto,
    epp.nombre                                                          AS estado,
    epp.color                                                           AS colorEstado,
    epp.id                                                              AS idEstado,
    pp.cantidad_bandejas_reales                                         AS cantidadBandejas,
    pp.codigo_sobre                                                     AS codigoSobre,
    CONCAT(u.apellido, ' ', u.nombre)                                   AS cliente
FROM pedido_producto pp
    LEFT JOIN pedido p ON p.id = pp.id_pedido
    LEFT JOIN tipo_variedad tv ON pp.id_tipo_variedad = tv.id
    LEFT JOIN tipo_sub_producto tsp ON tv.id_tipo_sub_producto = tsp.id
    LEFT JOIN tipo_producto tp ON tsp.id_tipo_producto = tp.id
    LEFT JOIN tipo_bandeja tb ON pp.id_tipo_bandeja = tb.id
    LEFT JOIN estado_pedido_producto epp ON epp.id = pp.id_estado_pedido_producto
    LEFT JOIN usuario u ON u.id = p.id_cliente
WHERE pp.fecha_baja IS NULL
  AND pp.id_estado_pedido_producto IN (1, 2);
