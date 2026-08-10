create sql security invoker view view_obra_social as
select `os`.`id`            AS `id`,
       `os`.`nombre`        AS `nombre`,
       `os`.`habilitado`    AS `habilitado`,
       `os`.`codigo_interno` AS `codigoInterno`
from `babyplant`.`obra_social` `os`
where `os`.`fecha_baja` is null;
