<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Entity\Constants\ConstanteEstadoFactura;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity
 * @ORM\Table(name="factura")
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Factura
{
    use Auditoria;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=FacturaDetalle::class, mappedBy="factura", cascade={"persist", "remove"})
     */
    private $detalles;

    /**
     * @ORM\ManyToOne(targetEntity=ModoPago::class)
     * @ORM\JoinColumn(name="id_modo_pago", referencedColumnName="id", nullable=true)
     */
    private $modoPago;

    /**
     * @ORM\Column(name="fecha", type="datetime", nullable=false)
     */
    protected $fecha;

    /**
     * @ORM\Column(name="tipo_cambio", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tipoCambio;

    /**
     * @ORM\Column(name="tipo_moneda", type="string", length=3, nullable=false, options={"default"="ARS"})
     */
    private $tipoMoneda;

    /**
     * @ORM\Column(name="redondeo_usd", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $redondeoUSD;

    /**
     * @ORM\Column(name="redondeo_ars", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $redondeoARS;

    /**
     * @ORM\Column(name="numero_factura", type="string", length=50)
     */
    private $numeroFactura;

    /**
     * @ORM\ManyToOne(targetEntity=Proveedor::class)
     * @ORM\JoinColumn(name="id_proveedor", referencedColumnName="id", nullable=false)
     */
    private $proveedor;

    /**
     * @ORM\ManyToOne(targetEntity=TipoGrupo::class)
     * @ORM\JoinColumn(name="id_tipo_grupo", referencedColumnName="id", nullable=true)
     */
    private $tipoGrupo;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoFactura::class)
     * @ORM\JoinColumn(name="id_estado_factura", referencedColumnName="id", nullable=true)
     */
    private $estadoFactura;

    /**
     * @ORM\OneToMany(targetEntity=EstadoFacturaHistorico::class, mappedBy="factura", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\OneToMany(targetEntity=ImputacionPagoFactura::class, mappedBy="factura", cascade={"persist", "remove"})
     */
    private $imputaciones;


    public function __construct()
    {
        $this->detalles = new ArrayCollection();
        $this->imputaciones = new ArrayCollection();
        $this->historicoEstados = new ArrayCollection();
    }

    public function getDetalles(): Collection
    {
        return $this->detalles;
    }

    /**
     * @param mixed $detalles
     */
    public function setDetalles($detalles): void
    {
        $this->detalles = $detalles;

        foreach ($detalles as $detalle){
            $detalle->setFactura($this);
        }
    }

    public function addDetalle(FacturaDetalle $detalle): self
    {
        if (!$this->detalles->contains($detalle)) {
            $this->detalles[] = $detalle;
            $detalle->setFactura($this);
        }
        return $this;
    }

    public function removeDetalle(FacturaDetalle $detalle): self
    {
        if ($this->detalles->removeElement($detalle)) {
            if ($detalle->getFactura() === $this) {
                $detalle->setFactura(null);
            }
        }
        return $this;
    }

    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->detalles as $detalle) {
            $total += (float) $detalle->getMontoTotal();
        }
        
        // Sumar el redondeo según la moneda de la factura
        if ($this->tipoMoneda === 'USD') {
            $total += $this->getRedondeoUSD();
        } else {
            $total += $this->getRedondeoARS();
        }
        
        return $total;
    }

    public function getMontoARS(): float
    {
        $total = $this->getTotal();
        $tipoCambio = (float) $this->tipoCambio;

        if ($this->tipoMoneda === 'USD') {
            return $total * $tipoCambio;
        }

        return $total;
    }

    public function getMontoUSD(): float
    {
        $total = $this->getTotal();
        $tipoCambio = (float) $this->tipoCambio;

        if ($this->tipoMoneda === 'USD') {
            return $total;
        }

        if ($tipoCambio > 0) {
            return $total / $tipoCambio;
        }

        return 0.0;
    }

    public function getSaldoPendienteARS(): float
    {
        $montoTotal = $this->getMontoARS();
        $totalPagado = $this->getTotalPagado();
        
        // Si la factura está en USD, el totalPagado está en USD, necesito convertirlo
        if ($this->tipoMoneda === 'USD') {
            $tipoCambio = (float) $this->tipoCambio;
            if ($tipoCambio > 0) {
                $totalPagadoARS = $totalPagado * $tipoCambio;
            } else {
                $totalPagadoARS = 0;
            }
        } else {
            $totalPagadoARS = $totalPagado;
        }
        
        $saldo = max(0, $montoTotal - $totalPagadoARS);
        
        // Si el saldo es menor a 0,01, considerarlo como 0 para evitar errores de redondeo
        if ($saldo < 0.01) {
            return 0;
        }
        
        return $saldo;
    }

    public function getSaldoPendienteUSD(): float
    {
        $montoTotal = $this->getMontoUSD();
        $totalPagado = $this->getTotalPagado();
        
        // Si la factura está en ARS, el totalPagado está en ARS, necesito convertirlo
        if ($this->tipoMoneda === 'ARS') {
            $tipoCambio = (float) $this->tipoCambio;
            if ($tipoCambio > 0) {
                $totalPagadoUSD = $totalPagado / $tipoCambio;
            } else {
                $totalPagadoUSD = 0;
            }
        } else {
            $totalPagadoUSD = $totalPagado;
        }
        
        $saldo = max(0, $montoTotal - $totalPagadoUSD);
        
        // Si el saldo es menor a 0,01, considerarlo como 0 para evitar errores de redondeo
        if ($saldo < 0.01) {
            return 0;
        }
        
        return $saldo;
    }

    public function getNumeroFactura(): ?string
    {
        return $this->numeroFactura;
    }

    public function setNumeroFactura(string $numeroFactura): self
    {
        $this->numeroFactura = $numeroFactura;
        return $this;
    }

    public function __toString(): string
    {
        return $this->numeroFactura ? 'Factura #' . $this->numeroFactura : 'Factura';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModoPago(): ?ModoPago
    {
        return $this->modoPago;
    }

    public function setModoPago(?ModoPago $modoPago): self
    {
        $this->modoPago = $modoPago;
        return $this;
    }

    public function getFecha()
    {
        return $this->fecha;
    }

    public function setFecha($fecha): void
    {
        $this->fecha = $fecha;
    }

    public function getTipoCambio(): ?string
    {
        return $this->tipoCambio;
    }

    public function setTipoCambio(?string $tipoCambio): self
    {
        $this->tipoCambio = $tipoCambio;
        return $this;
    }

    public function getTipoMoneda(): ?string
    {
        return $this->tipoMoneda;
    }

    public function setTipoMoneda(string $tipoMoneda): self
    {
        $this->tipoMoneda = $tipoMoneda;
        return $this;
    }

    public function getRedondeoUSD(): ?float
    {
        return $this->redondeoUSD ? (float) $this->redondeoUSD : 0;
    }

    public function setRedondeoUSD(?float $redondeoUSD): self
    {
        $this->redondeoUSD = $redondeoUSD;
        return $this;
    }

    public function getRedondeoARS(): ?float
    {
        return $this->redondeoARS ? (float) $this->redondeoARS : 0;
    }

    public function setRedondeoARS(?float $redondeoARS): self
    {
        $this->redondeoARS = $redondeoARS;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProveedor()
    {
        return $this->proveedor;
    }

    /**
     * @param mixed $proveedor
     */
    public function setProveedor($proveedor): void
    {
        $this->proveedor = $proveedor;
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

    public function getEstadoFactura(): ?EstadoFactura
    {
        return $this->estadoFactura;
    }

    public function setEstadoFactura(?EstadoFactura $estadoFactura): self
    {
        $this->estadoFactura = $estadoFactura;
        return $this;
    }

    public function getImputaciones(): Collection
    {
        return $this->imputaciones;
    }

    public function addImputacion(ImputacionPagoFactura $imputacion): self
    {
        if (!$this->imputaciones->contains($imputacion)) {
            $this->imputaciones[] = $imputacion;
            $imputacion->setFactura($this);
        }
        return $this;
    }

    public function removeImputacion(ImputacionPagoFactura $imputacion): self
    {
        $this->imputaciones->removeElement($imputacion);

        // Comentado para evitar el error 1048 (NotNullConstraintViolationException)
        // durante el SoftDelete de Doctrine.
        // if ($imputacion->getFactura() === $this) {
        //     $imputacion->setFactura(null);
        // }

        return $this;
    }

    public function getTotalPagado(): float
    {
        $total = 0;
        foreach ($this->imputaciones as $imputacion) {
            $total += $imputacion->getMonto();
        }
        return $total;
    }

    public function calcularEstadoId(): int
    {
        $pagado = $this->getTotalPagado();

        if ($pagado <= 0) {
            return ConstanteEstadoFactura::PENDIENTE;
        }

        // Tolerancia de redondeo de centavos
        if ($pagado >= ($this->getTotal() - 0.01)) {
            return ConstanteEstadoFactura::PAGA;
        }

        return ConstanteEstadoFactura::PAGO_PARCIAL;
    }

    public function getHistoricoEstados()
    {
        return $this->historicoEstados;
    }

    public function setHistoricoEstados($historicoEstados): void
    {
        $this->historicoEstados = $historicoEstados;
    }

    public function addHistoricoEstado(EstadoFacturaHistorico $historicoEstado): self
    {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setFactura($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoFacturaHistorico $historicoEstado): self
    {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            if ($historicoEstado->getFactura() === $this) {
                $historicoEstado->setFactura(null);
            }
        }

        return $this;
    }

}