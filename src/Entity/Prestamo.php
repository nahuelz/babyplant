<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Util\Decimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Prestamo
 *
 * @ORM\Table(name="prestamo")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Prestamo
{
    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Empleado::class, inversedBy="prestamos")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=false)
     */
    private $empleado;

    /**
     * @ORM\Column(name="fecha", type="date", nullable=false)
     */
    private $fecha;

    /**
     * @ORM\Column(name="monto", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $monto;

    /**
     * @ORM\Column(name="observaciones", type="text", nullable=true)
     */
    private $observaciones;

    /**
     * @ORM\OneToMany(targetEntity=ConceptoLiquidacion::class, mappedBy="prestamo")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $cuotas;

    public function __construct()
    {
        $this->cuotas = new ArrayCollection();
    }

    public function getId(): ?int
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

    public function getMonto()
    {
        return $this->monto;
    }

    public function setMonto($monto): void
    {
        $this->monto = $monto;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }

    public function getCuotas()
    {
        return $this->cuotas;
    }

    public function getMontoPagado(?ConceptoLiquidacion $conceptoExcluido = null): string
    {
        $total = '0';

        foreach ($this->cuotas as $cuota) {
            if ($cuota === $conceptoExcluido || $cuota->getFechaBaja() !== null) {
                continue;
            }

            $total = Decimal::add($total, (string) $cuota->getImporte(), 2);
        }

        return $total;
    }

    public function getSaldoPendiente(?ConceptoLiquidacion $conceptoExcluido = null): string
    {
        return Decimal::sub((string) $this->monto, $this->getMontoPagado($conceptoExcluido), 2);
    }

    public function isPagado(): bool
    {
        return Decimal::comp($this->getSaldoPendiente(), '0', 2) <= 0;
    }
}
