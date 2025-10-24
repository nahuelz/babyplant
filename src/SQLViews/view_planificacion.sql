create definer = root@`%` view view_planificacion as
select `pp`.`id`                                                                                 AS `id`,
       `tb`.`color`                                                                              AS `colorBandeja`,
       `tp`.`color`                                                                              AS `colorProducto`,
       concat(`pp`.`id`, ' - ', `tv`.`nombre`, ' - <strong class=tipo-bandeja>', case
                                                                                     when
                                                                                         `pp`.`cantidad_bandejas_pedidas` =
                                                                                         floor(`pp`.`cantidad_bandejas_pedidas`)
                                                                                         then format(`pp`.`cantidad_bandejas_pedidas`, 0)
                                                                                     else `pp`.`cantidad_bandejas_pedidas` end,
              ' (X', `tb`.`nombre`, ')</strong>')                                                AS `title`,
       concat(`epp`.`class_name`, ' ', `tp`.`nombre`, ' ', `epp`.`nombre`)                       AS `className`,
       `pp`.`fecha_siembra_planificacion`                                                        AS `fechaSiembraPlanificacion`,
       concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`, ' (x', `tb`.`nombre`, ')') AS `producto`,
       `tp`.`nombre`                                                                             AS `tipoProducto`,
       `epp`.`nombre`                                                                            AS `estado`,
       `epp`.`color`                                                                             AS `colorEstado`,
       `epp`.`id`                                                                                AS `idEstado`,
       `pp`.`cantidad_bandejas_reales`                                                           AS `cantidadBandejas`,
       `pp`.`codigo_sobre`                                                                       AS `codigoSobre`,
       concat(`u`.`apellido`, ' ', `u`.`nombre`)                                                 AS `cliente`
from (((((((`babyplant`.`pedido_producto` `pp` left join `babyplant`.`pedido` `p`
            on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant`.`tipo_variedad` `tv`
           on (`pp`.`id_tipo_variedad` = `tv`.`id`)) left join `babyplant`.`tipo_sub_producto` `tsp`
          on (`tv`.`id_tipo_sub_producto` = `tsp`.`id`)) left join `babyplant`.`tipo_producto` `tp`
         on (`tsp`.`id_tipo_producto` = `tp`.`id`)) left join `babyplant`.`tipo_bandeja` `tb`
        on (`pp`.`id_tipo_bandeja` = `tb`.`id`)) left join `babyplant`.`estado_pedido_producto` `epp`
       on (`epp`.`id` = `pp`.`id_estado_pedido_producto`)) left join `babyplant`.`usuario` `u`
      on (`u`.`id` = `p`.`id_cliente`))
where `pp`.`fecha_baja` is null
  and `pp`.`id_estado_pedido_producto` in (1, 2);

