create
definer = root@localhost function compareDestinatarios(_roles varchar(5000), _destinatarios varchar(5000)) returns tinyint(1)
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
END;

