create definer = root@`%` view _view_remito_precio_producto as
select `r`.`id`                                                                                AS `id_remito`,
       `ep`.`id`                                                                               AS `id_entrega_producto`,
       `ep`.`precio_unitario` * `ep`.`cantidad_bandejas`                                       AS `total_producto`,
       `r`.`id_tipo_descuento`                                                                 AS `id_tipo_descuento`,
       `td`.`nombre`                                                                           AS `tipo_descuento`,
       `r`.`cantidad_descuento`                                                                AS `cantidad_descuento`,
       sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) over ( partition by `r`.`id`)    AS `total_remito`,
        case
            when `r`.`id_tipo_descuento` = 1 then `ep`.`precio_unitario` * `ep`.`cantidad_bandejas` -
                                                  `r`.`cantidad_descuento` *
                                                  (`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) /
                                                  sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`)
                over ( partition by `r`.`id`)
            when `r`.`id_tipo_descuento` = 2 then `ep`.`precio_unitario` * `ep`.`cantidad_bandejas` *
                                                  (1 - `r`.`cantidad_descuento` / 100)
            else `ep`.`precio_unitario` * `ep`.`cantidad_bandejas` end                          AS `total_producto_con_descuento`,
       ifnull((select sum(`p`.`monto`) from `babyplant`.`pago` `p` where `p`.`id_remito` = `r`.`id`), 0) *
       (`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) /
       sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) over ( partition by `r`.`id`)    AS `pago_producto`,
        case
            when `r`.`id_tipo_descuento` = 1 then `ep`.`precio_unitario` * `ep`.`cantidad_bandejas` -
                                                  `r`.`cantidad_descuento` *
                                                  (`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) /
                                                  sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`)
                over ( partition by `r`.`id`)
            when `r`.`id_tipo_descuento` = 2 then `ep`.`precio_unitario` * `ep`.`cantidad_bandejas` *
                                                  (1 - `r`.`cantidad_descuento` / 100)
            else `ep`.`precio_unitario` * `ep`.`cantidad_bandejas` end -
        ifnull((select sum(`p`.`monto`) from `babyplant`.`pago` `p` where `p`.`id_remito` = `r`.`id`), 0) *
        (`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) / sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`)
            over ( partition by `r`.`id`) AS `monto_pendiente_producto`
from (((`babyplant`.`remito` `r` left join `babyplant`.`entrega` `e`
        on (`e`.`id_remito` = `r`.`id`)) left join `babyplant`.`entrega_producto` `ep`
       on (`ep`.`id_entrega` = `e`.`id`)) left join `babyplant`.`tipo_descuento` `td`
      on (`td`.`id` = `r`.`id_tipo_descuento`));

