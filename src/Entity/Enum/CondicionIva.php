<?php

namespace App\Entity\Enum;

enum CondicionIva: string
{
    case RESPONSABLE_INSCRIPTO = 'RESPONSABLE_INSCRIPTO';
    case MONOTRIBUTISTA = 'MONOTRIBUTISTA';
    case EXENTO = 'EXENTO';
    case CONSUMIDOR_FINAL = 'CONSUMIDOR_FINAL';

    public function getDescripcion(): string
    {
        return match ($this) {
            self::RESPONSABLE_INSCRIPTO => 'Responsable Inscripto',
            self::MONOTRIBUTISTA => 'Monotributista',
            self::EXENTO => 'Exento',
            self::CONSUMIDOR_FINAL => 'Consumidor Final',
        };
    }

    public static function getChoices(): array
    {
        return [
            'Responsable Inscripto' => self::RESPONSABLE_INSCRIPTO,
            'Monotributista' => self::MONOTRIBUTISTA,
            'Exento' => self::EXENTO,
            'Consumidor Final' => self::CONSUMIDOR_FINAL,
        ];
    }
}
