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






}