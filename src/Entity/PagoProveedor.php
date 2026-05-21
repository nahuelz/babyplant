<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Auditoria;
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
     * Importe del pago.
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private string $importe;

    /**
     * Fecha efectiva del pago.
     *
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $fechaPago;

    /**
     * Transferencia, efectivo, cheque, etc.
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private ?string $medioPago = null;

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

    public function __construct()
    {
        $this->fechaPago = new \DateTimeImmutable();
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

    public function getImporte(): float
    {
        return (float) $this->importe;
    }

    public function setImporte(float $importe): self
    {
        $this->importe = (string) $importe;

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

    public function getMedioPago(): ?string
    {
        return $this->medioPago;
    }

    public function setMedioPago(
        ?string $medioPago
    ): self {
        $this->medioPago = $medioPago;

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
}