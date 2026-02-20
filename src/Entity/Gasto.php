<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="gasto")
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Gasto
{
    use Auditoria;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TipoConcepto::class)
     * @ORM\JoinColumn(name="id_tipo_concepto", referencedColumnName="id", nullable=false)
     */
    private $concepto;

    /**
     * @ORM\ManyToOne(targetEntity=TipoSubConcepto::class)
     * @ORM\JoinColumn(name="id_tipo_sub_concepto", referencedColumnName="id", nullable=true)
     */
    private $subConcepto;

    /**
     * @ORM\Column(name="monto", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $monto;

    /**
     * @ORM\ManyToOne(targetEntity=ModoPago::class)
     * @ORM\JoinColumn(name="id_modo_pago", referencedColumnName="id", nullable=false)
     */
    private $modoPago;

    /**
     * @ORM\Column(name="fecha", type="datetime", nullable=false)
     */
    protected $fecha;


    public function __toString(): string
    {
        return $this->concepto ? $this->concepto->getNombre() : 'Gasto';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConcepto(): ?TipoConcepto
    {
        return $this->concepto;
    }

    public function setConcepto(?TipoConcepto $concepto): self
    {
        $this->concepto = $concepto;
        return $this;
    }

    public function getMonto(): ?string
    {
        return $this->monto;
    }

    public function setMonto(string $monto): self
    {
        $this->monto = $monto;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModoPago(): ?ModoPago
    {
        return $this->modoPago;
    }

    /**
     * @param mixed $modoPago
     */
    public function setModoPago(?ModoPago $modoPago): self
    {
        $this->modoPago = $modoPago;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFecha()
    {
        return $this->fecha;
    }

    /**
     * @param mixed $fecha
     */
    public function setFecha($fecha): void
    {
        $this->fecha = $fecha;
    }

    /**
     * @return mixed
     */
    public function getSubConcepto()
    {
        return $this->subConcepto;
    }

    /**
     * @param mixed $subConcepto
     */
    public function setSubConcepto($subConcepto): void
    {
        $this->subConcepto = $subConcepto;
    }





}