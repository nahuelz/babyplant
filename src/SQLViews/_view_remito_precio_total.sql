create definer = root@`%` view _view_remito_precio_total as
select `r`.`id`                                                                                                       AS `id_remito`,
       sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`)                                                         AS `total`,
       `r`.`id_tipo_descuento`                                                                                        AS `id_tipo_descuento`,
       `td`.`nombre`                                                                                                  AS `tipo_descuento`,
       `r`.`cantidad_descuento`                                                                                       AS `cantidad_descuento`,
       case
           when `r`.`id_tipo_descuento` = 1 then sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) -
                                                 `r`.`cantidad_descuento`
           when `r`.`id_tipo_descuento` = 2 then sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) *
                                                 (1 - `r`.`cantidad_descuento` / 100)
           else sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) end                                            AS `total_con_descuento`,
       ifnull((select sum(`p`.`monto`) from `babyplant`.`pago` `p` where `p`.`id_remito` = `r`.`id`),
              0)                                                                                                      AS `total_pagado`,
       case
           when `r`.`id_tipo_descuento` = 1 then sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) -
                                                 `r`.`cantidad_descuento` - ifnull((select sum(`p`.`monto`)
                                                                                    from `babyplant`.`pago` `p`
                                                                                    where `p`.`id_remito` = `r`.`id`),
                                                                                   0)
           when `r`.`id_tipo_descuento` = 2 then
               sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) * (1 - `r`.`cantidad_descuento` / 100) -
               ifnull((select sum(`p`.`monto`) from `babyplant`.`pago` `p` where `p`.`id_remito` = `r`.`id`), 0)
           else sum(`ep`.`precio_unitario` * `ep`.`cantidad_bandejas`) -
                ifnull((select sum(`p`.`monto`) from `babyplant`.`pago` `p` where `p`.`id_remito` = `r`.`id`),
                       0) end                                                                                         AS `monto_pendiente`
from (((`babyplant`.`remito` `r` left join `babyplant`.`entrega` `e`
        on (`e`.`id_remito` = `r`.`id`)) left join `babyplant`.`entrega_producto` `ep`
       on (`ep`.`id_entrega` = `e`.`id`)) left join `babyplant`.`tipo_descuento` `td`
      on (`td`.`id` = `r`.`id_tipo_descuento`))
group by `r`.`id`;

