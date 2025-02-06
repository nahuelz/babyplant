create definer = root@localhost view view_tipo_sub_producto as
select `tu`.`id` AS `id`, `tu`.`nombre` AS `nombre`, `tu`.`habilitado` AS `habilitado`
from `babyplant2`.`tipo_sub_producto` `tu`;

