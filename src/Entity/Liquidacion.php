<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Util\Decimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Liquidacion
 *
 * @ORM\Table(name="liquidacion", uniqueConstraints={@ORM\UniqueConstraint(name="uniq_empleado_periodo", columns={"id_empleado","fecha_desde","fecha_hasta"})})
 * @ORM\Entity
 */
class Liquidacion {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Empleado::class, inversedBy="liquidaciones")
     * @ORM\JoinColumn(name="id_empleado", referencedColumnName="id", nullable=false)
     */
    private $empleado;

    /**
     * Periodo YYYY-MM, para facilitar búsquedas/reportes (ej: "2026-08").
     *
     * @ORM\Column(name="periodo", type="string", length=7, nullable=false)
     */
    private $periodo;

    /**
     * @ORM\Column(name="fecha_desde", type="date", nullable=false)
     */
    private $fechaDesde;

    /**
     * @ORM\Column(name="fecha_hasta", type="date", nullable=false)
     */
    private $fechaHasta;

    /**
     * Snapshot de la modalidad al momento de generar la liquidación.
     *
     * @ORM\ManyToOne(targetEntity=TipoModalidadPago::class)
     * @ORM\JoinColumn(name="id_tipo_modalidad_pago", referencedColumnName="id", nullable=false)
     */
    private $tipoModalidadPago;

    /**
     * Sueldo base + adicionales fijos que integran el bruto.
     *
     * @ORM\Column(name="sueldo_bruto", type="decimal", precision=12, scale=2, nullable=false, options={"default": 0})
     */
    private $sueldoBruto = 0;

    /**
     * @ORM\Column(name="deducciones", type="decimal", precision=12, scale=2, nullable=false, options={"default": 0})
     */
    private $deducciones = 0;

    /**
     * @ORM\Column(name="total_a_pagar", type="decimal", precision=12, scale=2, nullable=false, options={"default": 0})
     */
    private $totalAPagar = 0;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoLiquidacion::class)
     * @ORM\JoinColumn(name="id_estado", referencedColumnName="id", nullable=true)
     */
    private $estado;

    /**
     * @ORM\OneToMany(targetEntity=EstadoLiquidacionHistorico::class, mappedBy="liquidacion", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\OneToMany(targetEntity=ConceptoLiquidacion::class, mappedBy="liquidacion", cascade={"all"}, orphanRemoval=true)
     */
    private $conceptos;

    /**
     * @ORM\OneToMany(targetEntity=Adelanto::class, mappedBy="liquidacion")
     */
    private $adelantosImputados;

    /**
     * @ORM\OneToMany(targetEntity=PagoEmpleado::class, mappedBy="liquidacion", cascade={"all"})
     */
    private $pagos;

    /**
     * Resumen mensual. Null para liquidaciones mensuales y para semanas.
     *
     * @ORM\ManyToOne(targetEntity=Liquidacion::class, inversedBy="detallesSemanales")
     * @ORM\JoinColumn(name="id_padre", referencedColumnName="id", nullable=true)
     */
    private $padre;

    /**
     * Semanas de un resumen mensual. Vacío para empleados mensuales.
     *
     * @ORM\OneToMany(targetEntity=Liquidacion::class, mappedBy="padre", cascade={"all"}, orphanRemoval=true)
     * @ORM\OrderBy({"fechaDesde" = "ASC"})
     */
    private $detallesSemanales;

    /**
     * @ORM\Column(name="observaciones", type="text", nullable=true)
     */
    private $observaciones;

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
        $this->conceptos = new ArrayCollection();
        $this->adelantosImputados = new ArrayCollection();
        $this->pagos = new ArrayCollection();
        $this->detallesSemanales = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Liquidación N° ' . $this->getId();
    }

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

    public function getPeriodo(): ?string
    {
        return $this->periodo;
    }

    public function setPeriodo(string $periodo): void
    {
        $this->periodo = $periodo;
    }

    public function getFechaDesde()
    {
        return $this->fechaDesde;
    }

    public function setFechaDesde($fechaDesde): void
    {
        $this->fechaDesde = $fechaDesde;
    }

    public function getFechaHasta()
    {
        return $this->fechaHasta;
    }

    public function setFechaHasta($fechaHasta): void
    {
        $this->fechaHasta = $fechaHasta;
    }

    public function getTipoModalidadPago(): ?TipoModalidadPago
    {
        return $this->tipoModalidadPago;
    }

    public function setTipoModalidadPago(?TipoModalidadPago $tipoModalidadPago): void
    {
        $this->tipoModalidadPago = $tipoModalidadPago;
    }

    public function getSueldoBruto(): string
    {
        if (!$this->detallesSemanales->isEmpty()) {
            $total = '0';

            foreach ($this->detallesSemanales as $detalle) {
                $total = Decimal::add(
                    $total,
                    (string) $detalle->getSueldoBruto(),
                    2
                );
            }

            return $total;
        }

        return (string) $this->sueldoBruto;
    }

    public function setSueldoBruto($sueldoBruto): void
    {
        $this->sueldoBruto = $sueldoBruto;
    }

    public function getDeducciones(): string
    {
        if (!$this->detallesSemanales->isEmpty()) {
            $total = '0';

            foreach ($this->detallesSemanales as $detalle) {
                $total = Decimal::add(
                    $total,
                    (string) $detalle->getDeducciones(),
                    2
                );
            }

            return $total;
        }

        return (string) $this->deducciones;
    }

    public function setDeducciones($deducciones): void
    {
        $this->deducciones = $deducciones;
    }

    public function getSueldoNeto(): string
    {
        if (!$this->detallesSemanales->isEmpty()) {
            $total = '0';
            foreach ($this->detallesSemanales as $detalle) {
                $total = Decimal::add($total, $detalle->getSueldoNeto(), 2);
            }
            return $total;
        }

        return Decimal::sub((string) $this->sueldoBruto, (string) $this->deducciones, 2);
    }

    public function getTotalAPagar()
    {
        return $this->totalAPagar;
    }

    public function setTotalAPagar($totalAPagar): void
    {
        $this->totalAPagar = $totalAPagar;
    }

    public function getEstado(): ?EstadoLiquidacion
    {
        return $this->estado;
    }

    public function setEstado(?EstadoLiquidacion $estado): void
    {
        $this->estado = $estado;
    }

    public function getHistoricoEstados()
    {
        return $this->historicoEstados;
    }

    public function addHistoricoEstado(EstadoLiquidacionHistorico $historicoEstado): self
    {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setLiquidacion($this);
        }

        return $this;
    }

    public function getConceptos()
    {
        return $this->conceptos;
    }

    public function addConcepto(ConceptoLiquidacion $concepto): self
    {
        if (!$this->conceptos->contains($concepto)) {
            $this->conceptos[] = $concepto;
            $concepto->setLiquidacion($this);
        }

        return $this;
    }

    public function removeConcepto(ConceptoLiquidacion $concepto): self
    {
        if ($this->conceptos->removeElement($concepto)) {
            if ($concepto->getLiquidacion() === $this) {
                $concepto->setLiquidacion(null);
            }
        }

        return $this;
    }

    public function getAdelantosImputados()
    {
        return $this->adelantosImputados;
    }

    public function getPagos()
    {
        return $this->pagos;
    }

    public function addPago(PagoEmpleado $pago): self
    {
        if (!$this->pagos->contains($pago)) {
            $this->pagos[] = $pago;
            $pago->setLiquidacion($this);
        }

        return $this;
    }

    public function getPadre(): ?Liquidacion
    {
        return $this->padre;
    }

    public function setPadre(?Liquidacion $padre): void
    {
        $this->padre = $padre;
    }

    public function getDetallesSemanales()
    {
        return $this->detallesSemanales;
    }

    public function addDetalleSemanal(Liquidacion $detalle): self
    {
        if (!$this->detallesSemanales->contains($detalle)) {
            $this->detallesSemanales[] = $detalle;
            $detalle->setPadre($this);
        }

        return $this;
    }

    public function removeDetalleSemanal(Liquidacion $detalle): self
    {
        if ($this->detallesSemanales->removeElement($detalle)) {
            if ($detalle->getPadre() === $this) {
                $detalle->setPadre(null);
            }
        }

        return $this;
    }

    public function isResumen(): bool
    {
        return $this->padre === null;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }

    /**
     * Recalcula el total a pagar en base al sueldo neto y a los conceptos cargados
     * (INGRESO suma, DESCUENTO resta). Incluye el/los concepto/s ADELANTO ya imputados.
     */
    public function recalcularTotal(): void
    {
        $total = $this->getSueldoNeto();

        foreach ($this->conceptos as $concepto) {
            $signo = $concepto->getTipoConceptoLiquidacion()->esDescuento() ? '-1' : '1';
            $total = Decimal::add($total, Decimal::mul((string) $concepto->getImporte(), $signo, 2), 2);
        }

        $this->totalAPagar = $total;
    }

}
