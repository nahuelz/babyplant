create
definer = root@localhost procedure sp_index_remito(IN _fechaDesde datetime, IN _fechaHasta datetime, IN _idCliente int)
BEGIN
SELECT DISTINCT
    `r`.`id`                                                       AS `idRemito`,
    `pp`.`id`                                                      AS `idPedidoProducto`,
    CONCAT(pp.numero_orden,' ',substr(`tp`.`nombre`, 1, 3))        AS ordenSiembra,
    `r`.`fecha_creacion`                                           AS `fechaCreacion`,
    concat(`u`.`nombre`, ', ', `u`.`apellido`)                     AS `cliente`,
    u.id                                                           AS `idCliente`,
    concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`) AS `nombreProductoCompleto`,
    `tp`.`nombre`                                                  AS `nombreProducto`,
    `rp`.`cantidad_bandejas`                                       AS `cantidadBandejas`,
    `rp`.`precio_unitario`                                         AS `precioUnitario`,
    `rp`.`precio_unitario`*`rp`.`cantidad_bandejas`                AS `precioSubTotal`,
    precio_total.total                                             AS `precioTotal`,
    er.nombre                                                      AS estado,
    er.color                                                       AS colorEstado,
    er.id                                                          AS idEstado
FROM remito r
         LEFT JOIN remito_producto rp ON r.id = rp.id_remito
         LEFT JOIN pedido_producto pp ON pp.id = rp.id_pedido_producto
         LEFT JOIN usuario u ON id_cliente = u.id
         LEFT JOIN tipo_variedad tv ON tv.id = pp.id_tipo_variedad
         LEFT JOIN tipo_sub_producto tsp ON tsp.id = tv.id_tipo_sub_producto
         LEFT JOIN tipo_producto tp ON tp.id = tsp.id_tipo_producto
         LEFT JOIN _view_remito_precio_total precio_total ON precio_total.id_remito=r.id
         LEFT JOIN estado_remito er ON er.id = r.id_estado_remito
WHERE r.fecha_baja IS NULL
  AND (r.fecha_creacion >= _fechaDesde AND r.fecha_creacion <= _fechaHasta)
  AND (_idCliente IS NULL OR (_idCliente IS NOT NULL AND r.id_cliente = _idCliente))
ORDER BY r.id DESC
;
END;

