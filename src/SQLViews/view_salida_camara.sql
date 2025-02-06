create definer = root@localhost view view_salida_camara as
select `pp`.`id`                                                                    AS `id`,
       `tp`.`id`                                                                    AS `idProducto`,
       `pp`.`numero_orden`                                                          AS `numeroOrden`,
       concat(`pp`.`numero_orden`, ' ', substr(`tp`.`nombre`, 1, 3), ' - ', `tv`.`nombre`, ' ',
              `pp`.`cantidad_bandejas_reales`, ' (x', `tb`.`nombre`, ')')           AS `nombreCorto`,
       concat(`pp`.`cantidad_bandejas_reales`, ' (', `tb`.`nombre`, ')')            AS `cantidadTipoBandejabandeja`,
       concat(`u`.`nombre`, ' ', `u`.`apellido`)                                    AS `cliente`,
       `epp`.`id`                                                                   AS `idEstado`,
       `epp`.`nombre`                                                               AS `estado`,
       `epp`.`color`                                                                AS `colorEstado`,
       concat(`epp`.`class_name`, ' ', `tp`.`nombre`, ' ', `epp`.`nombre`)          AS `className`,
       `pp`.`fecha_salida_camara`                                                   AS `fechaSalidaCamara`,
       `pp`.`fecha_salida_camara_real`                                              AS `fechaSalidaCamaraReal`,
       concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`, ' ', `pp`.`cantidad_bandejas_reales`, ' (',
              `tb`.`nombre`, ')', ', Cliente: ', `u`.`nombre`, ' ', `u`.`apellido`) AS `descripcion`
from (((((((`babyplant2`.`pedido` `p` left join `babyplant2`.`pedido_producto` `pp`
            on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant2`.`tipo_variedad` `tv`
           on (`pp`.`id_tipo_variedad` = `tv`.`id`)) left join `babyplant2`.`tipo_sub_producto` `tsp`
          on (`tv`.`id_tipo_sub_producto` = `tsp`.`id`)) left join `babyplant2`.`tipo_producto` `tp`
         on (`tsp`.`id_tipo_producto` = `tp`.`id`)) left join `babyplant2`.`tipo_bandeja` `tb`
        on (`pp`.`id_tipo_bandeja` = `tb`.`id`)) left join `babyplant2`.`usuario` `u`
       on (`p`.`id_cliente` = `u`.`id`)) left join `babyplant2`.`estado_pedido_producto` `epp`
      on (`epp`.`id` = `pp`.`id_estado_pedido_producto`))
where `pp`.`fecha_baja` is null
  and `pp`.`id_estado_pedido_producto` in (4, 5);

