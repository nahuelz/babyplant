<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoConceptoLiquidacion
 *
 * @ORM\Table(name="tipo_concepto_liquidacion")
 * @ORM\Entity
 */
class TipoConceptoLiquidacion extends EntidadBasica {

    public const INGRESO   = 'INGRESO';
    public const DESCUENTO = 'DESCUENTO';

    /**
     * INGRESO | DESCUENTO
     *
     * @ORM\Column(name="tipo", type="string", length=20, nullable=false)
     */
    private $tipo;

    /**
     * @return string
     */
    public function getTipo(): string
    {
        return $this->tipo;
    }

    /**
     * @param string $tipo
     */
    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }

    public function esDescuento(): bool
    {
        return $this->tipo === self::DESCUENTO;
    }

}
