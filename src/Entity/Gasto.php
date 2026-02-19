<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity
 * @ORM\Table(name="gasto")
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConcepto(): ?string
    {
        return $this->concepto;
    }

    public function setConcepto(string $concepto): self
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
    public function getModoPago()
    {
        return $this->modoPago;
    }

    /**
     * @param mixed $modoPago
     */
    public function setModoPago($modoPago): void
    {
        $this->modoPago = $modoPago;
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



}