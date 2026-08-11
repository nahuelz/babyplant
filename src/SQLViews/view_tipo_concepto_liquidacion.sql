create sql security invoker view view_tipo_concepto_liquidacion as
select `tcl`.`id`           AS `id`,
       `tcl`.`nombre`       AS `nombre`,
       `tcl`.`descripcion`  AS `descripcion`,
       `tcl`.`codigo_interno` AS `codigoInterno`,
       `tcl`.`tipo`         AS `tipo`,
       `tcl`.`habilitado`   AS `habilitado`
from `babyplant`.`tipo_concepto_liquidacion` `tcl`
where `tcl`.`fecha_baja` is null;
