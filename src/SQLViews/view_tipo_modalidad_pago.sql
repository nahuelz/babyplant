create sql security invoker view view_tipo_modalidad_pago as
select `tmp`.`id`             AS `id`,
       `tmp`.`nombre`         AS `nombre`,
       `tmp`.`habilitado`     AS `habilitado`,
       `tmp`.`codigo_interno` AS `codigoInterno`
from `babyplant`.`tipo_modalidad_pago` `tmp`
where `tmp`.`fecha_baja` is null;
