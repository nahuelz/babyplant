<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Adelanto
 *
 * @ORM\Table(name="adelanto")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Adelanto {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Empleado::class, inversedBy="adelantos")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=false)
     */
    private $empleado;

    /**
     * @ORM\Column(name="fecha", type="date", nullable=false)
     */
    private $fecha;

    /**
     * @ORM\Column(name="importe", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $importe;

    /**
     * @ORM\Column(name="motivo", type="string", length=255, nullable=true)
     */
    private $motivo;

    /**
     * Null mientras el adelanto está pendiente de imputar a una liquidación.
     *
     * @ORM\ManyToOne(targetEntity=Liquidacion::class, inversedBy="adelantosImputados")
     * @ORM\JoinColumn(name="id_liquidacion", referencedColumnName="id", nullable=true)
     */
    private $liquidacion;

    public function getId()
    {
        return $this->id;
    }

    public function getEmpleado(): ?Empleado
    {
        return $this->empleado;
    }

    public function setEmpleado(?Empleado $empleado): void
    {
        $this->empleado = $empleado;
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

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function setMotivo(?string $motivo): void
    {
        $this->motivo = $motivo;
    }

    public function getLiquidacion(): ?Liquidacion
    {
        return $this->liquidacion;
    }

    public function setLiquidacion(?Liquidacion $liquidacion): void
    {
        $this->liquidacion = $liquidacion;
    }

    public function isImputado(): bool
    {
        return $this->liquidacion !== null;
    }

}
