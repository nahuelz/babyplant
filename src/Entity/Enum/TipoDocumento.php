<?php

namespace App\Entity\Enum;

enum TipoDocumento: string
{
    case CUIT = 'CUIT';
    case CUIL = 'CUIL';
    case DNI = 'DNI';
    case CDI = 'CDI';
    case PASAPORTE = 'PASAPORTE';

    public function getDescripcion(): string
    {
        return match ($this) {
            self::CUIT => 'CUIT',
            self::CUIL => 'CUIL',
            self::DNI => 'DNI',
            self::CDI => 'CDI',
            self::PASAPORTE => 'Pasaporte',
        };
    }

    public static function getChoices(): array
    {
        return [
            'CUIT' => self::CUIT,
            'CUIL' => self::CUIL,
            'DNI' => self::DNI,
            'CDI' => self::CDI,
            'Pasaporte' => self::PASAPORTE,
        ];
    }
}
