create definer = root@localhost view view_tipo_variedad as
select `t`.`id`         AS `id`,
       `t`.`nombre`     AS `nombre`,
       `tsp`.`nombre`   AS `nombre_sub_producto`,
       `tp`.`nombre`    AS `nombre_producto`,
       `t`.`habilitado` AS `habilitado`
from ((`babyplant2`.`tipo_variedad` `t` left join `babyplant2`.`tipo_sub_producto` `tsp`
       on (`tsp`.`id` = `t`.`id_tipo_sub_producto`)) left join `babyplant2`.`tipo_producto` `tp`
      on (`tsp`.`id_tipo_producto` = `tp`.`id`));

