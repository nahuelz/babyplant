create definer = root@`%` view view_salida_camara as
select `pp`.`id`                                                                                 AS `id`,
       concat(`pp`.`numero_orden`, ' ', substr(`tp`.`nombre`, 1, 3), ' - ', `tp`.`nombre`, ' ', `tv`.`nombre`,
              ' <strong class=tipo-bandeja>', `pp`.`cantidad_bandejas_reales`, ' (X', `tb`.`nombre`, ')</strong> ',
              `u`.`nombre`, ' ', `u`.`apellido`)                                                 AS `nombreCorto`,
       concat(`epp`.`class_name`, ' ', `tp`.`nombre`, ' ', `epp`.`nombre`)                       AS `className`,
       `pp`.`fecha_salida_camara`                                                                AS `fechaSalidaCamara`,
       `pp`.`fecha_salida_camara_real`                                                           AS `fechaSalidaCamaraReal`,
       `tp`.`color`                                                                              AS `colorProducto`,
       `tb`.`color`                                                                              AS `colorBandeja`,
       concat(`pp`.`numero_orden`, ' ', substr(`tp`.`nombre`, 1, 3))                             AS `orden`,
       `p`.`id`                                                                                  AS `idPedido`,
       `pp`.`observacion_camara`                                                                 AS `observacionCamara`,
       concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`, ' (x', `tb`.`nombre`, ')') AS `producto`,
       `tp`.`nombre`                                                                             AS `tipoProducto`,
       `epp`.`nombre`                                                                            AS `estado`,
       `epp`.`color`                                                                             AS `colorEstado`,
       `epp`.`id`                                                                                AS `idEstado`,
       `pp`.`cantidad_bandejas_reales`                                                           AS `cantidadBandejas`,
       `pp`.`codigo_sobre`                                                                       AS `codigoSobre`,
       concat(`u`.`apellido`, ' ', `u`.`nombre`)                                                 AS `cliente`,
       `pp`.`pasa_camara`                                                                        AS `pasaCamara`,
       `pp`.`camara_destino`                                                                     AS `camaraDestino`
from (((((((`babyplant`.`pedido` `p` left join `babyplant`.`pedido_producto` `pp`
            on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant`.`tipo_variedad` `tv`
           on (`pp`.`id_tipo_variedad` = `tv`.`id`)) left join `babyplant`.`tipo_sub_producto` `tsp`
          on (`tv`.`id_tipo_sub_producto` = `tsp`.`id`)) left join `babyplant`.`tipo_producto` `tp`
         on (`tsp`.`id_tipo_producto` = `tp`.`id`)) left join `babyplant`.`tipo_bandeja` `tb`
        on (`pp`.`id_tipo_bandeja` = `tb`.`id`)) left join `babyplant`.`usuario` `u`
       on (`p`.`id_cliente` = `u`.`id`)) left join `babyplant`.`estado_pedido_producto` `epp`
      on (`epp`.`id` = `pp`.`id_estado_pedido_producto`))
where `pp`.`fecha_baja` is null
  and `pp`.`id_estado_pedido_producto` in (4, 5);

