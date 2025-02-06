create definer = root@localhost view view_tipo_producto as
select `tu`.`id`                  AS `id`,
       `tu`.`nombre`              AS `nombre`,
       `tu`.`habilitado`          AS `habilitado`,
       `tm`.`nombre`              AS `ultima_mesada`,
       `tu`.`catidad_dias_camara` AS `cantidad_dias_camara`
from (`babyplant2`.`tipo_producto` `tu` left join `babyplant2`.`tipo_mesada` `tm`
      on (`tu`.`id_ultima_mesada` = `tm`.`id`));

