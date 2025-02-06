create definer = root@localhost view view_tipo_usuario as
select `tu`.`id` AS `id`, `tu`.`nombre` AS `nombre`, `tu`.`habilitado` AS `habilitado`
from `babyplant2`.`tipo_usuario` `tu`;

