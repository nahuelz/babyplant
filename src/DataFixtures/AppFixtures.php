<?php

namespace App\DataFixtures;

use App\Entity\EstadoPedido;
use App\Entity\EstadoPedidoProducto;
use App\Entity\GlobalConfig;
use App\Entity\Grupo;
use App\Entity\TipoUsuario;
use App\Entity\Usuario;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

     public function __construct(UserPasswordEncoderInterface $passwordEncoder)
     {
         $this->passwordEncoder = $passwordEncoder;
     }

    public function load(ObjectManager $manager): void
    {
        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(1);
        $estadoPedidoProducto->setHabilitado(1);
        $estadoPedidoProducto->setNombre('PENDIENTE');
        $estadoPedidoProducto->setColor('label-light-dark');
        $estadoPedidoProducto->setIcono('fas fa-folder-plus');
        $estadoPedidoProducto->setColorIcono('dark');
        $manager->persist($estadoPedidoProducto);

        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(2);
        $estadoPedidoProducto->setHabilitado(1);
        $estadoPedidoProducto->setNombre('PLANIFICADO');
        $estadoPedidoProducto->setColor('label-light-warning');
        $estadoPedidoProducto->setIcono('fas fa-list-ul');
        $estadoPedidoProducto->setColorIcono('warning');
        $manager->persist($estadoPedidoProducto);

        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(3);
        $estadoPedidoProducto->setHabilitado(1);
        $estadoPedidoProducto->setNombre('SEMBRADO');
        $estadoPedidoProducto->setColor('label-light-success');
        $estadoPedidoProducto->setIcono('fas fa-leaf');
        $estadoPedidoProducto->setColorIcono('success');
        $manager->persist($estadoPedidoProducto);

        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(4);
        $estadoPedidoProducto->setHabilitado(1);
        $estadoPedidoProducto->setNombre('EN CAMARA');
        $estadoPedidoProducto->setColor('label-light-primary');
        $estadoPedidoProducto->setIcono('fas fa-list-ul');
        $estadoPedidoProducto->setColorIcono('primary');
        $manager->persist($estadoPedidoProducto);

        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(5);
        $estadoPedidoProducto->setHabilitado(1);
        $estadoPedidoProducto->setNombre('EN INVERNACULO');
        $estadoPedidoProducto->setColor('label-light-info');
        $estadoPedidoProducto->setIcono('fas fa-home');
        $estadoPedidoProducto->setColorIcono('info');
        $manager->persist($estadoPedidoProducto);

        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(6);
        $estadoPedidoProducto->setHabilitado(1);
        $estadoPedidoProducto->setNombre('ENTREGADO');
        $estadoPedidoProducto->setColor('label-light-success');
        $estadoPedidoProducto->setIcono('fas fa-check');
        $estadoPedidoProducto->setColorIcono('success');
        $manager->persist($estadoPedidoProducto);

        $estadoPedidoProducto = new EstadoPedidoProducto();
        $estadoPedidoProducto->setCodigoInterno(7);
        $estadoPedidoProducto->setHabilitado(1);
        $estadoPedidoProducto->setNombre('CANCELADO');
        $estadoPedidoProducto->setColor('label-light-danger');
        $estadoPedidoProducto->setIcono('fas fa-exclamation-triangle');
        $estadoPedidoProducto->setColorIcono('danger');
        $manager->persist($estadoPedidoProducto);
        
        $estadoPedido = new EstadoPedido();
        $estadoPedido->setCodigoInterno(1);
        $estadoPedido->setHabilitado(1);
        $estadoPedido->setNombre('NUEVO');
        $manager->persist($estadoPedido);

        $grupo = new Grupo();
        $grupo->setNombre("Administrador");
        $grupo->addRole("ROLE_USER");
        $grupo->addRole("ROLE_ALL");
        $grupo->setDescripcion("Grupo para administrador");
        $manager->persist($grupo);

        $grupo = new Grupo();
        $grupo->setNombre("Configuracion");
        $grupo->addRole("ROLE_BANDEJA_CRUD");
        $grupo->setDescripcion("Grupo configuracion");
        $manager->persist($grupo);

        $grupo = new Grupo();
        $grupo->setNombre("Cliente");
        $grupo->addRole("ROLE_USER");
        $grupo->setDescripcion("Grupo cliente");
        $manager->persist($grupo);

        $grupo = new Grupo();
        $grupo->setNombre("Siembra");
        $grupo->addRole("ROLE_SIEMBRA");
        $grupo->setDescripcion("Grupo siembra");
        $manager->persist($grupo);

        $tipoUsuario = new TipoUsuario();
        $tipoUsuario->setHabilitado(1);
        $tipoUsuario->setCodigoInterno(2);
        $tipoUsuario->setNombre('Cliente');
        $manager->persist($tipoUsuario);

        $tipoUsuario2 = new TipoUsuario();
        $tipoUsuario2->setHabilitado(1);
        $tipoUsuario2->setCodigoInterno(2);
        $tipoUsuario2->setNombre('Tecnico');
        $manager->persist($tipoUsuario2);

        $user = new Usuario();
        $user->setHabilitado(1);
        $user->setNombre('admin');
        $user->setApellido('admin');
        $user->setUsername('admin');
        $user->setEmail('admin@admin.com');
        $user->setTieneRazonSocial(0);
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                '123456'
            )
        );
        $manager->persist($user);
        $manager->flush();

        $globalConfi = new GlobalConfig();
        $manager->persist($globalConfi);

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view _view_pedido_producto_mesada as
                                            select `pp`.`id` AS `id_pedido_producto`, group_concat(`tm`.`nombre` separator ' / ') AS `mesada`
                                            from (((`babyplant2`.`pedido_producto` `pp` left join `babyplant2`.`pedido_producto_mesada` `ppm`
                                                    on (`pp`.`id` = `ppm`.`id_pedio_producto`)) left join `babyplant2`.`mesada` `m`
                                                   on (`ppm`.`id_mesada` = `m`.`id`)) left join `babyplant2`.`tipo_mesada` `tm`
                                                  on (`m`.`id_tipo_mesada` = `tm`.`id`))
                                            group by `pp`.`id`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view _view_remito_precio_total as
                                            select `r`.`id` AS `id_remito`, sum(`rp`.`precio_unitario` * `rp`.`cantidad_bandejas`) AS `total`
                                            from (`babyplant2`.`remito` `r` left join `babyplant2`.`remito_producto` `rp` on (`r`.`id` = `rp`.`id_remito`))
                                            group by `r`.`id`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_bandeja as
                                            select `b`.`id` AS `id`, `b`.`nombre` AS `nombre`, `b`.`estandar` AS `estandar`, `b`.`habilitado` AS `habilitado`
                                            from `babyplant2`.`tipo_bandeja` `b`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_entrada_camara as
                                            select `pp`.`id`                                                                              AS `id`,
                                                   `pp`.`numero_orden`                                                                    AS `numeroOrden`,
                                                   concat(`pp`.`numero_orden`, ' ', substr(`tp`.`nombre`, 1, 3), ' - ', `tp`.`nombre`, ' ', `tsp`.`nombre`, ' ',
                                                          `tv`.`nombre`, ' ', `pp`.`cantidad_bandejas_reales`, ' (x', `tb`.`nombre`, ')') AS `title`,
                                                   concat(`pp`.`cantidad_bandejas_reales`, ' (', `tb`.`nombre`, ')')                      AS `cantidadTipoBandejabandeja`,
                                                   concat(`u`.`nombre`, ' ', `u`.`apellido`)                                              AS `cliente`,
                                                   `epp`.`nombre`                                                                         AS `estado`,
                                                   `epp`.`color`                                                                          AS `colorEstado`,
                                                   if(`epp`.`nombre` <> 'SEMBRADO', concat(`epp`.`class_name`, ' ', `tp`.`nombre`, ' ', `epp`.`nombre`),
                                                      concat(`epp`.`class_name`, ' ', `tp`.`nombre`))                                     AS `className`,
                                                   `pp`.`fecha_siembra_real`                                                              AS `fechaSiembraReal`,
                                                   concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`, ' ', `pp`.`cantidad_bandejas_reales`, ' (',
                                                          `tb`.`nombre`, ')', ', Cliente: ', `u`.`nombre`, ' ', `u`.`apellido`)           AS `descripcion`
                                            from ((((((((`babyplant2`.`pedido` `p` left join `babyplant2`.`pedido_producto` `pp`
                                                         on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant2`.`tipo_variedad` `tv`
                                                        on (`pp`.`id_tipo_variedad` = `tv`.`id`)) left join `babyplant2`.`tipo_sub_producto` `tsp`
                                                       on (`tv`.`id_tipo_sub_producto` = `tsp`.`id`)) left join `babyplant2`.`tipo_producto` `tp`
                                                      on (`tsp`.`id_tipo_producto` = `tp`.`id`)) left join `babyplant2`.`tipo_bandeja` `tb`
                                                     on (`pp`.`id_tipo_bandeja` = `tb`.`id`)) left join `babyplant2`.`usuario` `u`
                                                    on (`p`.`id_cliente` = `u`.`id`)) left join `babyplant2`.`estado_pedido` `ep`
                                                   on (`ep`.`id` = `p`.`id_estado_pedido`)) left join `babyplant2`.`estado_pedido_producto` `epp`
                                                  on (`epp`.`id` = `pp`.`id_estado_pedido_producto`))
                                            where `pp`.`fecha_baja` is null
                                              and `pp`.`id_estado_pedido_producto` in (3, 4);");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_log_auditoria as
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
                                                  on (`la`.`id_usuario_creacion` = `u`.`id`));");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_salida_camara as
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
                                            from ((((((((`babyplant2`.`pedido` `p` left join `babyplant2`.`pedido_producto` `pp`
                                                         on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant2`.`tipo_variedad` `tv`
                                                        on (`pp`.`id_tipo_variedad` = `tv`.`id`)) left join `babyplant2`.`tipo_sub_producto` `tsp`
                                                       on (`tv`.`id_tipo_sub_producto` = `tsp`.`id`)) left join `babyplant2`.`tipo_producto` `tp`
                                                      on (`tsp`.`id_tipo_producto` = `tp`.`id`)) left join `babyplant2`.`tipo_bandeja` `tb`
                                                     on (`pp`.`id_tipo_bandeja` = `tb`.`id`)) left join `babyplant2`.`usuario` `u`
                                                    on (`p`.`id_cliente` = `u`.`id`)) left join `babyplant2`.`estado_pedido` `ep`
                                                   on (`ep`.`id` = `p`.`id_estado_pedido`)) left join `babyplant2`.`estado_pedido_producto` `epp`
                                                  on (`epp`.`id` = `pp`.`id_estado_pedido_producto`))
                                            where `pp`.`fecha_baja` is null
                                              and `pp`.`id_estado_pedido_producto` in (4, 5);");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_siembra as
                                            select `pp`.`id`                                                                    AS `id`,
                                                   `p`.`id`                                                                     AS `idPedido`,
                                                   concat(`pp`.`numero_orden`, ' ', substr(`tp`.`nombre`, 1, 3), ' - ', `tp`.`nombre`, ' ', `tsp`.`nombre`, ' ',
                                                          `tv`.`nombre`, ' - Bandejas: <strong class=band-', `tb`.`nombre`, '>', `pp`.`cantidad_bandejas_reales`,
                                                          ' (X', `tb`.`nombre`, ')</strong>', ' Semillas: ', `pp`.`cantidad_semillas`, ' - Cliente: ', `u`.`nombre`,
                                                          ' ', `u`.`apellido`)                                                  AS `title`,
                                                   concat(`pp`.`cantidad_bandejas_reales`, ' (', `tb`.`nombre`, ')')            AS `cantidadTipoBandejabandeja`,
                                                   concat(`u`.`nombre`, ' ', `u`.`apellido`)                                    AS `cliente`,
                                                   `epp`.`nombre`                                                               AS `estado`,
                                                   `epp`.`color`                                                                AS `colorEstado`,
                                                   concat(`epp`.`class_name`, ' ', `tp`.`nombre`, ' ', `epp`.`nombre`)          AS `className`,
                                                   `pp`.`fecha_siembra_planificacion`                                           AS `fechaSiembraPlanificacion`,
                                                   concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`, ' ', `pp`.`cantidad_bandejas_reales`, ' (',
                                                          `tb`.`nombre`, ')', ', Cliente: ', `u`.`nombre`, ' ', `u`.`apellido`) AS `descripcion`
                                            from ((((((((`babyplant2`.`pedido` `p` left join `babyplant2`.`pedido_producto` `pp`
                                                         on (`p`.`id` = `pp`.`id_pedido`)) left join `babyplant2`.`tipo_variedad` `tv`
                                                        on (`pp`.`id_tipo_variedad` = `tv`.`id`)) left join `babyplant2`.`tipo_sub_producto` `tsp`
                                                       on (`tv`.`id_tipo_sub_producto` = `tsp`.`id`)) left join `babyplant2`.`tipo_producto` `tp`
                                                      on (`tsp`.`id_tipo_producto` = `tp`.`id`)) left join `babyplant2`.`tipo_bandeja` `tb`
                                                     on (`pp`.`id_tipo_bandeja` = `tb`.`id`)) left join `babyplant2`.`usuario` `u`
                                                    on (`p`.`id_cliente` = `u`.`id`)) left join `babyplant2`.`estado_pedido` `ep`
                                                   on (`ep`.`id` = `p`.`id_estado_pedido`)) left join `babyplant2`.`estado_pedido_producto` `epp`
                                                  on (`epp`.`id` = `pp`.`id_estado_pedido_producto`))
                                            where `pp`.`fecha_baja` is null
                                              and `pp`.`id_estado_pedido_producto` in (2, 3);");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_tipo_mesada as
                                            select `tm`.`id`         AS `id`,
                                                   `tm`.`numero`     AS `nombre`,
                                                   `tm`.`capacidad`  AS `capacidad`,
                                                   `tm`.`ocupado`    AS `ocupado`,
                                                   `tp`.`nombre`     AS `tipoMesada`,
                                                   `tm`.`habilitado` AS `habilitado`
                                            from (`babyplant2`.`tipo_mesada` `tm` left join `babyplant2`.`tipo_producto` `tp`
                                                  on (`tp`.`id` = `tm`.`id_tipo_producto`))
                                            order by `tm`.`numero`;
                                            
                                            ");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_tipo_origen_semilla as
                                            select `t`.`id` AS `id`, `t`.`nombre` AS `nombre`, `t`.`habilitado` AS `habilitado`
                                            from `babyplant2`.`tipo_origen_semilla` `t`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_tipo_producto as
                                            select `tu`.`id`                  AS `id`,
                                                   `tu`.`nombre`              AS `nombre`,
                                                   `tu`.`habilitado`          AS `habilitado`,
                                                   `tm`.`nombre`              AS `ultima_mesada`,
                                                   `tu`.`catidad_dias_camara` AS `cantidad_dias_camara`
                                            from (`babyplant2`.`tipo_producto` `tu` left join `babyplant2`.`tipo_mesada` `tm`
                                                  on (`tu`.`id_ultima_mesada` = `tm`.`id`));");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_tipo_sub_producto as
                                            select `tu`.`id` AS `id`, `tu`.`nombre` AS `nombre`, `tu`.`habilitado` AS `habilitado`
                                            from `babyplant2`.`tipo_sub_producto` `tu`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_tipo_usuario as
                                            select `tu`.`id` AS `id`, `tu`.`nombre` AS `nombre`, `tu`.`habilitado` AS `habilitado`
                                            from `babyplant2`.`tipo_usuario` `tu`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_tipo_usuario as
                                            select `tu`.`id` AS `id`, `tu`.`nombre` AS `nombre`, `tu`.`habilitado` AS `habilitado`
                                            from `babyplant2`.`tipo_usuario` `tu`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_tipo_variedad as
                                            select `t`.`id` AS `id`, `t`.`nombre` AS `nombre`, `t`.`habilitado` AS `habilitado`
                                            from `babyplant2`.`tipo_variedad` `t`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost function compareDestinatarios(_roles varchar(5000), _destinatarios varchar(5000)) returns tinyint(1)
BEGIN
    DECLARE v_destinatario VARCHAR(5000);
    DECLARE i INT DEFAULT 1;
    DECLARE result TINYINT DEFAULT 0;

    SET v_destinatario = REPLACE(SUBSTRING(SUBSTRING_INDEX(_destinatarios, ',', i), LENGTH(SUBSTRING_INDEX(_destinatarios, ',', i -1)) + 1), ',', '');

    WHILE(v_destinatario <> '') DO
            -- SET v_chip = SUBSTRING(v_chip, 1, 15);

            IF( _roles LIKE CONCAT('%', v_destinatario, '%')) THEN
                SET result = 1;
            END IF;

            SET i = i + 1;

            SET v_destinatario = REPLACE(SUBSTRING(SUBSTRING_INDEX(_destinatarios, ',', i), LENGTH(SUBSTRING_INDEX(_destinatarios, ',', i -1)) + 1), ',', '');

        END WHILE;

    RETURN result;
END;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("CREATE TABLE `sessions` (
                                            `sess_id` varbinary(128) NOT NULL,
                                              `sess_data` blob NOT NULL,
                                              `sess_lifetime` int(10) unsigned NOT NULL,
                                              `sess_time` int(10) unsigned NOT NULL,
                                              `user_id` int(11) DEFAULT NULL,
                                              `user_ip` varchar(255) COLLATE utf8_spanish_ci DEFAULT NULL,
                                              PRIMARY KEY (`sess_id`) USING BTREE
                                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost view view_usuario as
                                            select `u`.`id`                                                                                    AS `id`,
                                                   `u`.`username`                                                                              AS `username`,
                                                   `u`.`email`                                                                                 AS `email`,
                                                   `u`.`nombre`                                                                                AS `nombre`,
                                                   `u`.`apellido`                                                                              AS `apellido`,
                                                   if(`u`.`celular` is not null, concat(`u`.`telefono`, ' / ', `u`.`celular`), `u`.`telefono`) AS `telefono`,
                                                   `ug`.`grupos`                                                                               AS `grupos`,
                                                   `u`.`last_seen`                                                                             AS `last_seen`,
                                                   if(`s`.`sess_lifetime` is null or unix_timestamp() > max(`s`.`sess_lifetime`), 0, 1)        AS `logueado`,
                                                   `u`.`habilitado`                                                                            AS `habilitado`,
                                                   group_concat(concat(cast(`s`.`sess_id` as char charset utf8mb4), '___',
                                                                       date_format(from_unixtime(`s`.`sess_time`), '%Y-%m-%d %H:%i:%s'), '___',
                                                                       date_format(from_unixtime(`s`.`sess_lifetime`), '%Y-%m-%d %H:%i'), '___', `s`.`user_ip`)
                                                                order by `s`.`sess_time` DESC separator '____')                                AS `sesiones`
                                            from ((`babyplant2`.`usuario` `u` join (select `ug`.`usuario_id`                         AS `usuario_id`,
                                                                                           group_concat(`g`.`nombre` separator ', ') AS `grupos`
                                                                                    from (`babyplant2`.`usuario_grupo` `ug` join `babyplant2`.`grupo` `g`
                                                                                          on (`ug`.`grupo_id` = `g`.`id`))
                                                                                    group by `ug`.`usuario_id`) `ug`
                                                   on (`ug`.`usuario_id` = `u`.`id`)) left join `babyplant2`.`sessions` `s` on (`s`.`user_id` = `u`.`id`))
                                            where `u`.`fecha_baja` is null
                                            group by `u`.`id`;");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost procedure sp_index_pedido(IN _fechaDesde datetime, IN _fechaHasta datetime, IN _idCliente int)
BEGIN
    SELECT DISTINCT
        p.id                                                        AS id,
        pp.id                                                       AS idProducto,
        p.fecha_creacion                                            AS fechaCreacion,
        tv.nombre                                                   AS nombreVariedad,
        tp.nombre                                                   AS nombreProducto,
        tsp.nombre                                                  AS nombreSubProducto,
        CONCAT(tp.nombre,' ',tsp.nombre,' ',tv.nombre)              AS nombreProductoCompleto,
        CONCAT(u.nombre,', ',u.apellido)                            AS cliente,
        CONCAT(pp.cantidad_bandejas_pedidas,' (x',tb.nombre,')')    AS cantidadBandejas,
        tb.nombre                                                   AS tipoBandeja,
        pp.fecha_siembra_pedido                                     AS fechaSiembraPedido,
        pp.fecha_entrega_pedido                                     AS fechaEntregaPedido,
        epp.nombre                                                  AS estado,
        epp.color                                                   AS colorEstado,
        epp.id                                                      AS idEstado,
        if(pp.fecha_salida_camara_real is null, (to_days(curdate()) - to_days(cast(`pp`.`fecha_entrada_camara` as date))),
           (to_days(pp.fecha_salida_camara_real) - to_days(pp.fecha_entrada_camara))) AS `diasEnCamara`,
        if(pp.fecha_entrega is null, (to_days(curdate()) - to_days(cast(`pp`.`fecha_salida_camara_real` as date))),
           (to_days(pp.fecha_entrega) - to_days(pp.fecha_salida_camara_real))) AS `diasEnInvernaculo`,
        CONCAT(pp.numero_orden,' ',substr(`tp`.`nombre`, 1, 3))  AS ordenSiembra,
        ppm.mesada                                         AS mesada
    FROM pedido p
             LEFT JOIN pedido_producto pp ON pp.id_pedido = p.id
             LEFT JOIN tipo_variedad tv on tv.id = pp.id_tipo_variedad
             LEFT JOIN tipo_sub_producto tsp on tsp.id = tv.id_tipo_sub_producto
             LEFT JOIN tipo_producto tp on tp.id = tsp.id_tipo_producto
             LEFT JOIN tipo_bandeja tb on (tb.id = pp.id_tipo_bandeja)
             LEFT JOIN usuario u ON (u.id = p.id_cliente)
             LEFT JOIN estado_pedido ep ON (ep.id = p.id_estado_pedido)
             LEFT JOIN estado_pedido_producto epp ON (epp.id = pp.id_estado_pedido_producto)
             LEFT JOIN _view_pedido_producto_mesada ppm ON (pp.id = ppm.id_pedido_producto)
    WHERE p.fecha_baja IS NULL
      AND (p.fecha_creacion >= _fechaDesde AND p.fecha_creacion <= _fechaHasta)
      AND (_idCliente IS NULL OR (_idCliente IS NOT NULL AND p.id_cliente = _idCliente))
        ORDER BY p.id DESC
    ;
END;

");
        $statement->execute();

        $connection = $manager->getConnection();
        $statement = $connection->prepare("create definer = root@localhost procedure sp_index_remito(IN _fechaDesde datetime, IN _fechaHasta datetime, IN _idCliente int)
                                        BEGIN
                                            SELECT DISTINCT
                                                `r`.`id`                                                       AS `idRemito`,
                                                `pp`.`id`                                                      AS `idPedidoProducto`,
                                                `r`.`fecha_creacion`                                           AS `fechaCreacion`,
                                                concat(`u`.`nombre`, ', ', `u`.`apellido`)                     AS `cliente`,
                                                concat(`tp`.`nombre`, ' ', `tsp`.`nombre`, ' ', `tv`.`nombre`) AS `nombreProductoCompleto`,
                                                `tp`.`nombre`                                                  AS `nombreProducto`,
                                                `rp`.`cantidad_bandejas`                                       AS `cantidadBandejas`,
                                                `rp`.`precio_unitario`                                         AS `precioUnitario`,
                                                `rp`.`precio_unitario`*`rp`.`cantidad_bandejas`                AS `precioSubTotal`,
                                                precio_total.total                                             AS `precioTotal`
                                            from ((((((`babyplant2`.`remito` `r` left join `babyplant2`.`remito_producto` `rp`
                                                       on (`r`.`id` = `rp`.`id_remito`)) left join `babyplant2`.`pedido_producto` `pp`
                                                      on (`pp`.`id` = `rp`.`id_pedido_producto`)) left join `babyplant2`.`usuario` `u`
                                                     on (`r`.`id_cliente` = `u`.`id`)) left join `babyplant2`.`tipo_variedad` `tv`
                                                    on (`tv`.`id` = `pp`.`id_tipo_variedad`)) left join `babyplant2`.`tipo_sub_producto` `tsp`
                                                   on (`tsp`.`id` = `tv`.`id_tipo_sub_producto`)) left join `babyplant2`.`tipo_producto` `tp`
                                                  on (`tp`.`id` = `tsp`.`id_tipo_producto`)) left join babyplant2._view_remito_precio_total precio_total on (precio_total.id_remito=r.id)
                                            WHERE r.fecha_baja IS NULL
                                              AND (r.fecha_creacion >= _fechaDesde AND r.fecha_creacion <= _fechaHasta)
                                              AND (_idCliente IS NULL OR (_idCliente IS NOT NULL AND r.id_cliente = _idCliente))
                                            ORDER BY r.id DESC
                                            ;
                                        END;");
        $statement->execute();


    }
}
