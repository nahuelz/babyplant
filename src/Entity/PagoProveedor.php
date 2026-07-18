<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="pago_proveedor")
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class PagoProveedor
{
    use Auditoria;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Proveedor"
     * )
     * @ORM\JoinColumn(nullable=false)
     */
    private Proveedor $proveedor;

    /**
     * Monto del pago.
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private string $monto;

    /**
     * @ORM\Column(name="tipo_moneda", type="string", length=3, nullable=false, options={"default"="ARS"})
     */
    private $tipoMoneda;

    /**
     * @ORM\Column(name="tipo_cambio", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tipoCambio;


    /**
     * Fecha efectiva del pago.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $fechaPago;

    /**
     * @ORM\ManyToOne(targetEntity=ModoPago::class)
     * @ORM\JoinColumn(name="id_modo_pago", referencedColumnName="id")
     */
    private $modoPago;

    /**
     * Número de comprobante opcional.
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $numeroComprobante = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $observaciones = null;

    /**
     * @ORM\OneToMany(targetEntity=ImputacionPagoFactura::class, mappedBy="pagoProveedor", cascade={"persist", "remove"})
     */
    private Collection $imputaciones;

    public function __construct()
    {
        $this->fechaPago = new \DateTimeImmutable();
        $this->imputaciones = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProveedor(): Proveedor
    {
        return $this->proveedor;
    }

    public function setProveedor(
        Proveedor $proveedor
    ): self {
        $this->proveedor = $proveedor;

        return $this;
    }

    public function getMontoARS(): float
    {
        $total = $this->getMonto();
        $tipoCambio = (float) $this->tipoCambio;

        if ($this->tipoMoneda === 'USD') {
            return $total * $tipoCambio;
        }

        return $total;
    }

    public function getMontoUSD(): float
    {
        $total = $this->getMonto();
        $tipoCambio = (float) $this->tipoCambio;

        if ($this->tipoMoneda === 'USD') {
            return $total;
        }

        if ($tipoCambio > 0) {
            return $total / $tipoCambio;
        }

        return 0.0;
    }

    public function getMonto(): float
    {
        return (float) $this->monto;
    }

    public function setMonto(float $monto): self
    {
        $this->monto = (string) $monto;

        return $this;
    }

    public function getFechaPago(): \DateTimeImmutable
    {
        return $this->fechaPago;
    }

    public function setFechaPago(
        \DateTimeImmutable $fechaPago
    ): self {
        $this->fechaPago = $fechaPago;

        return $this;
    }

    public function getModoPago(): ?ModoPago
    {
        return $this->modoPago;
    }

    public function setModoPago(?ModoPago $modoPago): self {
        $this->modoPago = $modoPago;

        return $this;
    }

    public function getNumeroComprobante(): ?string
    {
        return $this->numeroComprobante;
    }

    public function setNumeroComprobante(
        ?string $numeroComprobante
    ): self {
        $this->numeroComprobante = $numeroComprobante;

        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(
        ?string $observaciones
    ): self {
        $this->observaciones = $observaciones;

        return $this;
    }

    public function getTipoMoneda(): string
    {
        return $this->tipoMoneda;
    }

    public function setTipoMoneda(string $tipoMoneda): void
    {
        $this->tipoMoneda = $tipoMoneda;
    }

    /**
     * @return mixed
     */
    public function getTipoCambio()
    {
        return $this->tipoCambio;
    }

    /**
     * @param mixed $tipoCambio
     */
    public function setTipoCambio($tipoCambio): void
    {
        $this->tipoCambio = $tipoCambio;
    }

    public function getImputaciones(): Collection
    {
        return $this->imputaciones;
    }

    public function addImputacion(ImputacionPagoFactura $imputacion): self
    {
        if (!$this->imputaciones->contains($imputacion)) {
            $this->imputaciones[] = $imputacion;
            $imputacion->setPagoProveedor($this);
        }

        return $this;
    }

    public function removeImputacion(ImputacionPagoFactura $imputacion): self
    {
        $this->imputaciones->removeElement($imputacion);

        // Comentado para evitar el error 1048 (NotNullConstraintViolationException)
        // durante el SoftDelete de Doctrine.
        // if ($imputacion->getPagoProveedor() === $this) {
        //     $imputacion->setPagoProveedor(null);
        // }

        return $this;
    }

    public function getTotalImputado(): float
    {
        $total = 0;
        foreach ($this->imputaciones as $imputacion) {
            $total += $imputacion->getMonto();
        }

        return $total;
    }

    public function getSaldoNoImputado(): float
    {
        return (float)$this->getMonto() - (float)$this->getTotalImputado();
    }

    public function getSaldoNoImputadoARS(): float
    {
        return $this->getMontoARS() - $this->getTotalImputadoARS();
    }


    public function getSaldoNoImputadoUSD(): float
    {
        return $this->getMontoUSD() - $this->getTotalImputadoUSD();
    }

    public function getTotalImputadoARS(): float
    {
        $total = 0;
        $tipoCambioPago = (float) $this->tipoCambio;

        foreach ($this->imputaciones as $imputacion) {
            $factura = $imputacion->getFactura();

            if ($factura->getTipoMoneda() === 'USD') {
                // La imputación está en USD, convertir a ARS usando el tipo de cambio del pago
                $total += $imputacion->getMonto() * $tipoCambioPago;
            } else {
                // La imputación ya está en ARS
                $total += $imputacion->getMonto();
            }
        }

        return $total;
    }


    public function getTotalImputadoUSD(): float
    {
        $total = 0;
        $tipoCambioPago = (float) $this->tipoCambio;

        foreach ($this->imputaciones as $imputacion) {
            $factura = $imputacion->getFactura();

            if ($factura->getTipoMoneda() === 'ARS') {
                // La imputación está en ARS, convertir a USD usando el tipo de cambio del pago
                $total += $imputacion->getMonto() / $tipoCambioPago;
            } else {
                // La imputación ya está en USD
                $total += $imputacion->getMonto();
            }
        }

        return $total;
    }
}