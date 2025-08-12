create
definer = root@`%` procedure sp_index_stock(IN _fechaDesde datetime, IN _fechaHasta datetime, IN _idCliente int)
    reads sql data
BEGIN
SELECT DISTINCT
    p.id                                                        AS id,
    pp.id                                                       AS idProducto,
    p.fecha_creacion                                            AS fechaCreacion,
    tv.nombre                                                   AS nombreVariedad,
    tp.nombre                                                   AS nombreProducto,
    tsp.nombre                                                  AS nombreSubProducto,
    CONCAT(tp.nombre,' ',tsp.nombre,' ',tv.nombre,' (x',tb.nombre,')') AS nombreProductoCompleto,
    CONCAT(u.nombre,', ',u.apellido)                            AS cliente,
    u.id                                                        AS idCliente,
    pp.cantidad_bandejas_pedidas                                AS cantidadBandejas,
    tb.nombre                                                   AS tipoBandeja,
    pp.fecha_siembra_pedido                                     AS fechaSiembraPedido,
    pp.fecha_entrega_pedido                                     AS fechaEntregaPedido,
    epp.nombre                                                  AS estado,
    epp.color                                                   AS colorEstado,
    tp.color                                                    AS colorProducto,
    epp.id                                                      AS idEstado,
    u.celular                                                   AS celular,
    if(pp.fecha_salida_camara_real is null, (to_days(curdate()) - to_days(cast(`pp`.`fecha_entrada_camara` as date))),
       (to_days(pp.fecha_salida_camara_real) - to_days(pp.fecha_entrada_camara))) AS `diasEnCamara`,
    if(epp.id in (5,6,7,8), (
        if(pp.fecha_entrega_pedido_real is null, (to_days(curdate()) - to_days(cast(`pp`.`fecha_salida_camara_real` as date))),
           (to_days(pp.fecha_entrega_pedido_real) - to_days(pp.fecha_salida_camara_real)))
        ), '-') AS `diasEnInvernaculo`,
    CONCAT(pp.numero_orden,' ',substr(`tp`.`nombre`, 1, 3))  AS ordenSiembra,
    if(tm2.numero is null, tm1.numero, CONCAT(tm1.numero,' / ',tm2.numero)) AS mesada
FROM pedido p
         LEFT JOIN pedido_producto pp ON pp.id_pedido = p.id
         LEFT JOIN tipo_variedad tv on tv.id = pp.id_tipo_variedad
         LEFT JOIN tipo_sub_producto tsp on tsp.id = tv.id_tipo_sub_producto
         LEFT JOIN tipo_producto tp on tp.id = tsp.id_tipo_producto
         LEFT JOIN tipo_bandeja tb on (tb.id = pp.id_tipo_bandeja)
         LEFT JOIN usuario u ON (u.id = p.id_cliente)
         LEFT JOIN estado_pedido_producto epp ON (epp.id = pp.id_estado_pedido_producto)
         LEFT JOIN mesada m1 on (m1.id = pp.id_mesada_uno)
         LEFT JOIN tipo_mesada tm1 on (tm1.id = m1.id_tipo_mesada)
         LEFT JOIN mesada m2 on (m2.id = pp.id_mesada_dos)
         LEFT JOIN tipo_mesada tm2 on (tm2.id = m2.id_tipo_mesada)
WHERE p.fecha_baja IS NULL
  AND (p.fecha_creacion >= _fechaDesde AND p.fecha_creacion <= _fechaHasta)
  AND (_idCliente IS NULL OR (_idCliente IS NOT NULL AND p.id_cliente = _idCliente))
  AND u.apellido LIKE '%STOCK%'
ORDER BY p.id DESC
;
END;

