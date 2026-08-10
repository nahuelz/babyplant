create sql security invoker view view_empleado as
select `e`.`id`                                AS `id`,
       concat(`e`.`apellido`, ' ', `e`.`nombre`) AS `nombreCompleto`,
       `e`.`nombre`                            AS `nombre`,
       `e`.`apellido`                          AS `apellido`,
       `e`.`dni`                               AS `dni`,
       `e`.`cuil`                              AS `cuil`,
       `e`.`nacionalidad`                      AS `nacionalidad`,
       `c`.`nombre`                            AS `categoria`,
       `tmp`.`nombre`                          AS `modalidadPago`,
       `e`.`fecha_ingreso`                     AS `fechaIngreso`,
       `e`.`activo`                            AS `activo`
from ((`babyplant`.`empleado` `e` left join `babyplant`.`categoria` `c`
       on (`e`.`id_categoria` = `c`.`id`)) left join `babyplant`.`tipo_modalidad_pago` `tmp`
      on (`e`.`id_tipo_modalidad_pago` = `tmp`.`id`))
where `e`.`fecha_baja` is null;
