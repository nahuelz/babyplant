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
    public const TIPO_AMBOS   = 'AMBOS';

    /**
     * @ORM\Column(name="tipo", type="string", length=20, nullable=false)
     */
    private $tipo;

    /**
     * @ORM\ManyToOne(targetEntity=TipoGrupo::class, inversedBy="tipoConceptos")
     * @ORM\JoinColumn(name="id_tipo_grupo", referencedColumnName="id", nullable=true)
     */
    private $tipoGrupo;

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

    public function getTipoGrupo(): ?TipoGrupo
    {
        return $this->tipoGrupo;
    }

    public function setTipoGrupo(?TipoGrupo $tipoGrupo): self
    {
        $this->tipoGrupo = $tipoGrupo;
        return $this;
    }

}