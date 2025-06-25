create definer = root@`%` view view_siembra as
select `pp`.`id`                                                                    AS `id`,
       `p`.`id`                                                                     AS `idPedido`,
       concat(`pp`.`numero_orden`, ' ', substr(`tp`.`nombre`, 1, 3), ' - ', `tp`.`nombre`, ' ', `tsp`.`nombre`, ' ',
              `tv`.`nombre`, ' - Bandejas: <strong class=band-', `tb`.`nombre`, '>', `pp`.`cantidad_bandejas_pedidas`,
              ' (X', `tb`.`nombre`, ')</strong>', ' Semillas: ', `pp`.`cantidad_semillas`, ' - Cliente: ', `u`.`nombre`,
              ' ', `u`.`apellido`)                                                  AS `title`,
       concat(`pp`.`cantidad_bandejas_pedidas`, ' (', `tb`.`nombre`, ')')           AS `cantidadTipoBandejabandeja`,
       concat(`u`.`nombre`, ' ', `u`.`apellido`)                                    AS `cliente`,
       `epp`.`nombre`                                                               AS `estado`,
       `epp`.`color`                                                                AS `colorEstado`,
       concat(`epp`.`class_name`, ' ', `tp`.`nombre`, ' ', `epp`.`nombre`)          AS `className`,
       `pp`.`fecha_siembra_planificacion`                                           AS `fechaSiembraPlanificacion`,
       concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`, ' ', `pp`.`cantidad_bandejas_pedidas`, ' (',
              `tb`.`nombre`, ')', ', Cliente: ', `u`.`nombre`, ' ', `u`.`apellido`) AS `descripcion`
from (((((((`babyplant`.`pedido` `p` left join `babyplant`.`pedido_producto` `pp`
            on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant`.`tipo_variedad` `tv`
           on (`pp`.`id_tipo_variedad` = `tv`.`id`)) left join `babyplant`.`tipo_sub_producto` `tsp`
          on (`tv`.`id_tipo_sub_producto` = `tsp`.`id`)) left join `babyplant`.`tipo_producto` `tp`
         on (`tsp`.`id_tipo_producto` = `tp`.`id`)) left join `babyplant`.`tipo_bandeja` `tb`
        on (`pp`.`id_tipo_bandeja` = `tb`.`id`)) left join `babyplant`.`usuario` `u`
       on (`p`.`id_cliente` = `u`.`id`)) left join `babyplant`.`estado_pedido_producto` `epp`
      on (`epp`.`id` = `pp`.`id_estado_pedido_producto`))
where `pp`.`fecha_baja` is null
  and `pp`.`id_estado_pedido_producto` in (2, 3);

