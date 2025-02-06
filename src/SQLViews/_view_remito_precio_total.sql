create definer = root@localhost view _view_remito_precio_total as
select `r`.`id` AS `id_remito`, sum(`rp`.`precio_unitario` * `rp`.`cantidad_bandejas`) AS `total`
from (`babyplant2`.`remito` `r` left join `babyplant2`.`remito_producto` `rp` on (`r`.`id` = `rp`.`id_remito`))
group by `r`.`id`;

