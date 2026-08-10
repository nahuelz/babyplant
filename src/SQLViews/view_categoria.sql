create sql security invoker view view_categoria as
select `c`.`id`           AS `id`,
       `c`.`nombre`       AS `nombre`,
       `c`.`habilitado`   AS `habilitado`,
       `c`.`codigo_interno` AS `codigoInterno`
from `babyplant`.`categoria` `c`
where `c`.`fecha_baja` is null;
