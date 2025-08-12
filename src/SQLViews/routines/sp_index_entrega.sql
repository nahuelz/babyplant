create
definer = root@`%` procedure sp_index_entrega(IN _fechaDesde datetime, IN _fechaHasta datetime, IN _idCliente int)
    reads sql data
BEGIN
SELECT DISTINCT
    e.id                                                        AS idEntrega,
    pp.id                                                       AS idPedidoProducto,
    CONCAT(pp.numero_orden,' ',substr(tp.nombre, 1, 3))         AS ordenSiembra,
    e.fecha_creacion                                            AS fechaCreacion,
    concat(u.nombre, ', ', u.apellido)                          AS cliente,
    concat(ue.nombre, ', ', ue.apellido)                        AS clienteEntrega,
    u.id                                                        AS idCliente,
    ue.id                                                       AS idClienteEntrega,
    concat(tp.nombre, ' ', tsp.nombre, ' ', tv.nombre)          AS nombreProductoCompleto,
    tp.nombre                                                   AS nombreProducto,
    ep.cantidad_bandejas                                        AS cantidadBandejas,
    ee.nombre                                                   AS estado,
    ee.color                                                    AS colorEstado,
    tp.color                                                    AS colorProducto,
    ee.id                                                       AS idEstado
FROM entrega e
         LEFT JOIN entrega_producto ep ON ep.id_entrega = e.id
         LEFT JOIN pedido_producto pp ON pp.id = ep.id_pedido_producto
         LEFT JOIN usuario u ON e.id_cliente = u.id
         LEFT JOIN usuario ue ON e.id_cliente_entrega = ue.id
         LEFT JOIN tipo_variedad tv ON tv.id = pp.id_tipo_variedad
         LEFT JOIN tipo_sub_producto tsp ON tsp.id = tv.id_tipo_sub_producto
         LEFT JOIN tipo_producto tp ON tp.id = tsp.id_tipo_producto
         LEFT JOIN estado_entrega ee ON ee.id = e.id_estado
WHERE e.fecha_baja IS NULL
  AND (e.fecha_creacion >= _fechaDesde AND e.fecha_creacion <= _fechaHasta)
  AND ((_idCliente IS NULL OR (_idCliente IS NOT NULL AND e.id_cliente = _idCliente)) OR (_idCliente IS NULL OR (_idCliente IS NOT NULL AND e.id_cliente_entrega = _idCliente)))
ORDER BY e.id DESC
;
END;

