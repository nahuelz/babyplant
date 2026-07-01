create definer = root@`%` view _view_remito_precio_producto as
with calculo_base as (select `r`.`id`                                                    AS `id_remito`,
                             `ep`.`id`                                                   AS `id_entrega_producto`,
                             round(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`, 2) AS `total_producto`,
                             `r`.`id_tipo_descuento`                                     AS `id_tipo_descuento`,
                             `td`.`nombre`                                               AS `tipo_descuento`,
                             `r`.`cantidad_descuento`                                    AS `cantidad_descuento`,
                             round(sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`)
                                       over ( partition by `r`.`id` order by `ep`.`id` rows between unbounded preceding and current row ),
                                   2)                                                    AS `bruto_acumulado`,
                             ifnull((select round(sum(`p`.`monto`), 2)
                                     from `babyplant`.`pago` `p`
                                     where `p`.`id_remito` = `r`.`id`), 0)               AS `total_pagado_remito`
                      from (((`babyplant`.`remito` `r` left join `babyplant`.`entrega` `e`
                              on (`e`.`id_remito` = `r`.`id`)) left join `babyplant`.`entrega_producto` `ep`
                             on (`ep`.`id_entrega` = `e`.`id`)) left join `babyplant`.`tipo_descuento` `td`
                            on (`td`.`id` = `r`.`id_tipo_descuento`))),
     calculo_descuentos as (select `calculo_base`.`id_remito`                   AS `id_remito`,
                                   `calculo_base`.`id_entrega_producto`         AS `id_entrega_producto`,
                                   `calculo_base`.`total_producto`              AS `total_producto`,
                                   `calculo_base`.`id_tipo_descuento`           AS `id_tipo_descuento`,
                                   `calculo_base`.`tipo_descuento`              AS `tipo_descuento`,
                                   `calculo_base`.`cantidad_descuento`          AS `cantidad_descuento`,
                                   `calculo_base`.`bruto_acumulado`             AS `bruto_acumulado`,
                                   `calculo_base`.`total_pagado_remito`         AS `total_pagado_remito`,
                                   case
                                       when `calculo_base`.`id_tipo_descuento` = 1 then round(
                                               `calculo_base`.`total_producto` - case
                                                                                     when `calculo_base`.`cantidad_descuento` >= `calculo_base`.`bruto_acumulado`
                                                                                         then `calculo_base`.`total_producto`
                                                                                     when
                                                                                         `calculo_base`.`cantidad_descuento` >
                                                                                         `calculo_base`.`bruto_acumulado` -
                                                                                         `calculo_base`.`total_producto`
                                                                                         then
                                                                                         `calculo_base`.`cantidad_descuento` -
                                                                                         (`calculo_base`.`bruto_acumulado` - `calculo_base`.`total_producto`)
                                                                                     else 0 end, 2)
                                       when `calculo_base`.`id_tipo_descuento` = 2 then round(
                                               `calculo_base`.`total_producto` *
                                               (1 - `calculo_base`.`cantidad_descuento` / 100), 2)
                                       else `calculo_base`.`total_producto` end AS `total_producto_con_descuento`
                            from `calculo_base`),
     calculo_totales as (select `calculo_descuentos`.`id_remito`                                    AS `id_remito`,
                                `calculo_descuentos`.`id_entrega_producto`                          AS `id_entrega_producto`,
                                `calculo_descuentos`.`total_producto`                               AS `total_producto`,
                                `calculo_descuentos`.`id_tipo_descuento`                            AS `id_tipo_descuento`,
                                `calculo_descuentos`.`tipo_descuento`                               AS `tipo_descuento`,
                                `calculo_descuentos`.`cantidad_descuento`                           AS `cantidad_descuento`,
                                `calculo_descuentos`.`bruto_acumulado`                              AS `bruto_acumulado`,
                                `calculo_descuentos`.`total_pagado_remito`                          AS `total_pagado_remito`,
                                `calculo_descuentos`.`total_producto_con_descuento`                 AS `total_producto_con_descuento`,
                                round(sum(`calculo_descuentos`.`total_producto_con_descuento`)
                                          over ( partition by `calculo_descuentos`.`id_remito`),
                                      2)                                                            AS `total_remito_con_descuento`
                         from `calculo_descuentos`)
select `calculo_totales`.`id_remito`                                 AS `id_remito`,
       `calculo_totales`.`id_entrega_producto`                       AS `id_entrega_producto`,
       `calculo_totales`.`total_producto`                            AS `total_producto`,
       `calculo_totales`.`id_tipo_descuento`                         AS `id_tipo_descuento`,
       `calculo_totales`.`tipo_descuento`                            AS `tipo_descuento`,
       `calculo_totales`.`cantidad_descuento`                        AS `cantidad_descuento`,
       `calculo_totales`.`bruto_acumulado`                           AS `total_remito`,
       `calculo_totales`.`total_producto_con_descuento`              AS `total_producto_con_descuento`,
       case
           when `calculo_totales`.`total_remito_con_descuento` > 0 then round(`calculo_totales`.`total_pagado_remito` *
                                                                              (`calculo_totales`.`total_producto_con_descuento` /
                                                                               `calculo_totales`.`total_remito_con_descuento`),
                                                                              2)
           else 0 end                                                AS `pago_producto`,
       case
           when `calculo_totales`.`total_remito_con_descuento` > 0 then round(
                   `calculo_totales`.`total_producto_con_descuento` - round(`calculo_totales`.`total_pagado_remito` *
                                                                            (`calculo_totales`.`total_producto_con_descuento` /
                                                                             `calculo_totales`.`total_remito_con_descuento`),
                                                                            2), 2)
           else `calculo_totales`.`total_producto_con_descuento` end AS `monto_pendiente_producto`
from `calculo_totales`;