create definer = root@localhost view view_log_auditoria as
select `la`.`id`                                 AS `id`,
       `p`.`id`                                  AS `idPedido`,
       `pp`.`id`                                 AS `idProducto`,
       `la`.`accion`                             AS `accion`,
       `la`.`modulo`                             AS `modulo`,
       concat(`u`.`nombre`, ' ', `u`.`apellido`) AS `usuario`,
       `la`.`fecha_creacion`                     AS `fecha`
from (((`babyplant2`.`log_auditoria` `la` left join `babyplant2`.`pedido` `p`
        on (`p`.`id` = `la`.`id_pedido`)) left join `babyplant2`.`pedido_producto` `pp`
       on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant2`.`usuario` `u`
      on (`la`.`id_usuario_creacion` = `u`.`id`));

