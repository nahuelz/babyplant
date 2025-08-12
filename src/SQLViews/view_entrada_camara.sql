create definer = root@`%` view view_entrada_camara as
select
    pp.id                                                                               AS id,
    concat(pp.numero_orden, ' ', substr(tp.nombre, 1, 3), ' - ', tp.nombre, ' ',
           tsp.nombre, ' ',  tv.nombre, ' <strong class=tipo-bandeja>',
           pp.cantidad_bandejas_reales, ' (X', tb.nombre,')</strong>',
           ' Semillas: ', pp.cantidad_semillas, ' - ', u.nombre, ' ',u.apellido)        AS title,
    if (epp.nombre <> 'SEMBRADO',
        concat(epp.class_name, ' ', tp.nombre, ' ', epp.nombre),
        concat(epp.class_name, ' ', tp.nombre))                                       AS className,
    tb.color                                                                         AS colorBandeja,
    tp.color                                                                         AS colorProducto,
    pp.fecha_siembra_real                                                            AS fechaSiembraReal
from pedido p
         LEFT JOIN pedido_producto pp ON p.id = pp.id_pedido
         LEFT JOIN tipo_variedad tv ON pp.id_tipo_variedad = tv.id
         LEFT JOIN tipo_sub_producto tsp ON tv.id_tipo_sub_producto = tsp.id
         LEFT JOIN tipo_producto tp ON tsp.id_tipo_producto = tp.id
         LEFT JOIN tipo_bandeja tb ON pp.id_tipo_bandeja = tb.id
         LEFT JOIN usuario u ON p.id_cliente = u.id
         LEFT JOIN estado_pedido_producto epp ON epp.id = pp.id_estado_pedido_producto
WHERE pp.fecha_baja is null AND pp.id_estado_pedido_producto in (3, 4);

