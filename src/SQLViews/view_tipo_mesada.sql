create definer = root@localhost view view_tipo_mesada as
select `tm`.`id`                AS `id`,
       `tm`.`numero`            AS `nombre`,
       `tm`.`capacidad`         AS `capacidad`,
       `cb`.`cantidad_bandejas` AS `ocupado`,
       `tp`.`nombre`            AS `tipoMesada`,
       `tm`.`habilitado`        AS `habilitado`
from ((`babyplant2`.`tipo_mesada` `tm` left join `babyplant2`.`tipo_producto` `tp`
       on (`tp`.`id` = `tm`.`id_tipo_producto`)) left join `babyplant2`.`_view_mesada_cantidad_bandejas` `cb`
      on (`tm`.`id` = `cb`.`id`))
order by `tm`.`numero`;

