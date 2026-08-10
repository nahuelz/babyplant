<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PagoEmpleado
 *
 * @ORM\Table(name="pago_empleado")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class PagoEmpleado {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Liquidacion::class, inversedBy="pagos")
     * @ORM\JoinColumn(name="id_liquidacion", referencedColumnName="id", nullable=false)
     */
    private $liquidacion;

    /**
     * @ORM\Column(name="fecha", type="date", nullable=false)
     */
    private $fecha;

    /**
     * @ORM\Column(name="importe", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $importe;

    /**
     * @ORM\ManyToOne(targetEntity=ModoPago::class)
     * @ORM\JoinColumn(name="id_modo_pago", referencedColumnName="id", nullable=true)
     */
    private $modoPago;

    /**
     * @ORM\Column(name="comprobante", type="string", length=255, nullable=true)
     */
    private $comprobante;

    /**
     * @ORM\Column(name="observaciones", type="text", nullable=true)
     */
    private $observaciones;

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

    public function getFecha()
    {
        return $this->fecha;
    }

    public function setFecha($fecha): void
    {
        $this->fecha = $fecha;
    }

    public function getImporte()
    {
        return $this->importe;
    }

    public function setImporte($importe): void
    {
        $this->importe = $importe;
    }

    public function getModoPago()
    {
        return $this->modoPago;
    }

    public function setModoPago($modoPago): void
    {
        $this->modoPago = $modoPago;
    }

    public function getComprobante(): ?string
    {
        return $this->comprobante;
    }

    public function setComprobante(?string $comprobante): void
    {
        $this->comprobante = $comprobante;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }

}
