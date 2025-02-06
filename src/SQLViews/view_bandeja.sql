create definer = root@localhost view view_bandeja as
select `b`.`id` AS `id`, `b`.`nombre` AS `nombre`, `b`.`estandar` AS `estandar`, `b`.`habilitado` AS `habilitado`
from `babyplant2`.`tipo_bandeja` `b`;

