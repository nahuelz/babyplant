create sql security invoker view view_banco as
select `b`.`id`             AS `id`,
       `b`.`nombre`         AS `nombre`,
       `b`.`habilitado`     AS `habilitado`,
       `b`.`codigo_interno` AS `codigoInterno`
from `babyplant`.`banco` `b`
where `b`.`fecha_baja` is null;
