create
    definer = root@`%` procedure sp_index_movimiento(IN _fechaDesde datetime, IN _fechaHasta datetime, IN _idCliente int)
    reads sql data
BEGIN
    SELECT DISTINCT
        m.id AS id,
        ifnull(uccp.id, uccu.id) AS idCliente
    FROM movimiento m
             LEFT JOIN cuenta_corriente_pedido ccp ON m.id_cuenta_corriente_pedido = ccp.id
             LEFT JOIN pedido p ON ccp.id = p.id_cuenta_corriente_pedido
             LEFT JOIN usuario uccp ON uccp.id = p.id_cliente
             LEFT JOIN cuenta_corriente_usuario ccu ON m.id_cuenta_corriente_usuario = ccu.id
             LEFT JOIN usuario uccu ON uccu.id_cuenta_corriente_usuario = ccu.id
    WHERE m.fecha_baja IS NULL
      AND (m.fecha_creacion >= _fechaDesde AND m.fecha_creacion <= _fechaHasta)
      AND (_idCliente IS NULL OR (_idCliente IS NOT NULL AND (uccp.id = _idCliente OR uccu.id = _idCliente)))
    ORDER BY m.id DESC
    ;
END;

