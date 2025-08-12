create
definer = root@`%` procedure sp_index_reserva(IN _fechaDesde datetime, IN _fechaHasta datetime, IN _idCliente int)
    reads sql data
BEGIN
SELECT DISTINCT
    `r`.`id`                                                       AS `idReserva`,
    `r`.`id_entrega`                                               AS `idEntrega`,
    `pp`.`id`                                                      AS `idPedidoProducto`,
    CONCAT(pp.numero_orden,' ',substr(`tp`.`nombre`, 1, 3))        AS ordenSiembra,
    `r`.`fecha_creacion`                                           AS `fechaCreacion`,
    concat(`u`.`nombre`, ', ', `u`.`apellido`)                     AS `cliente`,
    u.id                                                           AS `idCliente`,
    concat(`ur`.`nombre`, ', ', `ur`.`apellido`)                   AS `clienteReserva`,
    ur.id                                                          AS `idClienteReserva`,
    concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`) AS `nombreProductoCompleto`,
    `tp`.`nombre`                                                  AS `nombreProducto`,
    `r`.`cantidad_bandejas`                                        AS `cantidadBandejas`,
    er.nombre                                                      AS estado,
    er.color                                                       AS colorEstado,
    er.id                                                          AS idEstado,
    ep.nombre                                                      AS estadoPedidoProducto,
    ep.color                                                       AS colorEstadoPedidoProducto,
    tp.color                                                       AS colorProducto,
    ep.id                                                          AS idEstadoPedidoProducto
FROM reserva r
         LEFT JOIN pedido_producto pp ON pp.id = r.id_pedido_producto
         LEFT JOIN pedido p ON p.id = pp.id_pedido
         LEFT JOIN usuario ur ON r.id_cliente = ur.id
         LEFT JOIN usuario u ON p.id_cliente = u.id
         LEFT JOIN tipo_variedad tv ON tv.id = pp.id_tipo_variedad
         LEFT JOIN tipo_sub_producto tsp ON tsp.id = tv.id_tipo_sub_producto
         LEFT JOIN tipo_producto tp ON tp.id = tsp.id_tipo_producto
         LEFT JOIN estado_reserva er ON er.id = r.id_estado
         LEFT JOIN estado_pedido_producto ep ON ep.id = pp.id_estado_pedido_producto
WHERE r.fecha_baja IS NULL
  AND (r.fecha_creacion >= _fechaDesde AND r.fecha_creacion <= _fechaHasta)
  AND (_idCliente IS NULL OR (_idCliente IS NOT NULL AND r.id_cliente = _idCliente))
ORDER BY r.id DESC
;
END;

