create
definer = root@`%` procedure sp_index_situacion_cliente(IN _idCliente int) reads sql data
BEGIN
SELECT DISTINCT
    u.id            AS id,
    u.email         AS email,
    u.nombre        AS nombre,
    u.apellido      AS apellido,
    u.cuit          AS cuit,
    u.celular       AS celular,
    rs.razon_social AS razonSocial,
    cc.saldo        AS saldo,
    (COALESCE(scc.saldo, 0) + COALESCE(sp.saldo_pedidos, 0)) AS saldoFavor,
    COALESCE(dc.deuda, 0) AS deuda
FROM usuario u
         LEFT JOIN usuario_grupo ug on ug.usuario_id = u.id
         LEFT JOIN grupo g on g.id = ug.grupo_id
         LEFT JOIN razon_social rs on u.id_razon_social = rs.id
         LEFT JOIN cuenta_corriente_usuario cc on u.id_cuenta_corriente_usuario = cc.id
         LEFT JOIN _view_saldo_cuenta_corriente_usuario scc ON scc.id_usuario = u.id
         LEFT JOIN _view_saldo_pedidos_usuario sp ON sp.id_usuario = u.id
         LEFT JOIN _view_deuda_cliente dc ON dc.id_cliente = u.id
WHERE u.fecha_baja IS NULL
  AND g.id = 3 /* GRUPO 3 = CLINETE */
  AND (_idCliente IS NULL OR (_idCliente IS NOT NULL AND u.id = _idCliente));
END;