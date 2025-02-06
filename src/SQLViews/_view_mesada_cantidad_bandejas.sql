create definer = root@localhost view _view_mesada_cantidad_bandejas as
select `tm`.`id` AS `id`, sum(`m`.`cantidad_bandejas`) AS `cantidad_bandejas`
from (`babyplant2`.`tipo_mesada` `tm` left join `babyplant2`.`mesada` `m` on (`tm`.`id` = `m`.`id_tipo_mesada`))
group by `tm`.`id`;

