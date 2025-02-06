create definer = root@localhost view view_tipo_origen_semilla as
select `t`.`id` AS `id`, `t`.`nombre` AS `nombre`, `t`.`habilitado` AS `habilitado`
from `babyplant2`.`tipo_origen_semilla` `t`;

