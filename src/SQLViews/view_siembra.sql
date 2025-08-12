create definer = root@`%` view view_siembra as
select
    pp.id                                                                                   AS id,
    p.id                                                                                    AS idPedido,
    tb.color                                                                                AS colorBandeja,
    tp.color                                                                                AS colorProducto,
    concat(epp.class_name, ' ', tp.nombre, ' ', epp.nombre)                                 AS className,
    pp.fecha_siembra_planificacion                                                          AS fechaSiembraPlanificacion,
    concat(pp.numero_orden, ' ', substr(tp.nombre, 1, 3), ' - ',
           tp.nombre, ' ', tsp.nombre, ' ', tv.nombre, ' - <strong class=tipo-bandeja>',
           pp.cantidad_bandejas_pedidas, ' (X', tb.nombre,')</strong>', ' Semillas: ',
           pp.cantidad_semillas, ' - ', u.nombre, ' ',   u.apellido)                        AS `title`
from pedido p
    left join pedido_producto pp on (p.id = pp.id_pedido)
    left join tipo_variedad tv on (pp.id_tipo_variedad = tv.id)
    left join tipo_sub_producto tsp on (tv.id_tipo_sub_producto = tsp.id)
    left join tipo_producto tp on (tsp.id_tipo_producto = tp.id)
    left join tipo_bandeja tb on (pp.id_tipo_bandeja = tb.id)
    left join usuario u on (p.id_cliente = u.id)
    left join estado_pedido_producto epp on (epp.id = pp.id_estado_pedido_producto)
where pp.fecha_baja is null
  and pp.id_estado_pedido_producto in (2, 3);

