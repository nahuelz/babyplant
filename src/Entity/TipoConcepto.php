<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Concepto
 *
 * @ORM\Table(name="tipo_concepto")
 * @ORM\Entity
 */
class TipoConcepto extends EntidadBasica {

    public const TIPO_FACTURA = 'FACTURA';
    public const TIPO_GASTO   = 'GASTO';

    /**
     * @ORM\Column(name="tipo", type="string", length=20, nullable=false)
     */
    private $tipo;

    public function setNombre(string $nombre): self
    {
        parent::setNombre(mb_strtoupper($nombre, 'UTF-8'));
        return $this;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): self
    {
        $this->tipo = $tipo;
        return $this;
    }

}