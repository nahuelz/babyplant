create definer = root@localhost view view_usuario as
select `u`.`id`                                                                                    AS `id`,
       `u`.`username`                                                                              AS `username`,
       `u`.`email`                                                                                 AS `email`,
       `u`.`nombre`                                                                                AS `nombre`,
       `u`.`apellido`                                                                              AS `apellido`,
       `u`.`celular`                                                                               AS `celular`,
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
group by `u`.`id`;

