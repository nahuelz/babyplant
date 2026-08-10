<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;

/**
 * ConceptoLiquidacion
 *
 * @ORM\Table(name="concepto_liquidacion")
 * @ORM\Entity
 */
class ConceptoLiquidacion {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Liquidacion::class, inversedBy="conceptos")
     * @ORM\JoinColumn(name="id_liquidacion", referencedColumnName="id", nullable=true)
     */
    private $liquidacion;

    /**
     * @ORM\ManyToOne(targetEntity=TipoConceptoLiquidacion::class)
     * @ORM\JoinColumn(name="id_tipo_concepto_liquidacion", referencedColumnName="id", nullable=false)
     */
    private $tipoConceptoLiquidacion;

    /**
     * @ORM\Column(name="cantidad", type="decimal", precision=10, scale=2, nullable=false, options={"default": 1})
     */
    private $cantidad = 1;

    /**
     * @ORM\Column(name="valor_unitario", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $valorUnitario;

    /**
     * @ORM\Column(name="importe", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $importe;

    /**
     * @ORM\Column(name="descripcion", type="string", length=255, nullable=true)
     */
    private $descripcion;

    public function getId()
    {
        return $this->id;
    }

    public function getLiquidacion(): ?Liquidacion
    {
        return $this->liquidacion;
    }

    public function setLiquidacion(?Liquidacion $liquidacion): void
    {
        $this->liquidacion = $liquidacion;
    }

    public function getTipoConceptoLiquidacion(): ?TipoConceptoLiquidacion
    {
        return $this->tipoConceptoLiquidacion;
    }

    public function setTipoConceptoLiquidacion(?TipoConceptoLiquidacion $tipoConceptoLiquidacion): void
    {
        $this->tipoConceptoLiquidacion = $tipoConceptoLiquidacion;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }

    public function setCantidad($cantidad): void
    {
        $this->cantidad = $cantidad;
    }

    public function getValorUnitario()
    {
        return $this->valorUnitario;
    }

    public function setValorUnitario($valorUnitario): void
    {
        $this->valorUnitario = $valorUnitario;
    }

    public function getImporte()
    {
        return $this->importe;
    }

    public function setImporte($importe): void
    {
        $this->importe = $importe;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

}
