<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="movimiento_proveedor")
 */
class MovimientoProveedor
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
     *     targetEntity="App\Entity\CuentaCorrienteProveedor",
     *     inversedBy="movimientos"
     * )
     * @ORM\JoinColumn(nullable=false)
     */
    private ?CuentaCorrienteProveedor $cuentaCorrienteProveedor = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\TipoMovimiento"
     * )
     * @ORM\JoinColumn(nullable=false)
     */
    private TipoMovimiento $tipoMovimiento;

    /**
     * Negativo = aumenta deuda (factura recibida)
     * Positivo = reduce deuda (pago realizado)
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private string $monto;

    /**
     * Saldo luego de aplicar este movimiento.
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private string $saldoPosterior;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $descripcion = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\Factura"
     * )
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Factura $factura = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="App\Entity\PagoProveedor"
     * )
     * @ORM\JoinColumn(nullable=true)
     */
    private ?PagoProveedor $pagoProveedor = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCuentaCorrienteProveedor(): ?CuentaCorrienteProveedor
    {
        return $this->cuentaCorrienteProveedor;
    }

    public function setCuentaCorrienteProveedor(
        ?CuentaCorrienteProveedor $cuentaCorrienteProveedor
    ): self {
        $this->cuentaCorrienteProveedor = $cuentaCorrienteProveedor;

        return $this;
    }

    public function getTipoMovimiento(): TipoMovimiento
    {
        return $this->tipoMovimiento;
    }

    public function setTipoMovimiento(
        TipoMovimiento $tipoMovimiento
    ): self {
        $this->tipoMovimiento = $tipoMovimiento;

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

    public function getSaldoPosterior(): float
    {
        return (float) $this->saldoPosterior;
    }

    public function setSaldoPosterior(
        float $saldoPosterior
    ): self {
        $this->saldoPosterior = (string) $saldoPosterior;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(
        ?string $descripcion
    ): self {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getFactura(): ?Factura
    {
        return $this->factura;
    }

    public function setFactura(
        ?Factura $factura
    ): self {
        $this->factura = $factura;

        return $this;
    }

    public function getPagoProveedor(): ?PagoProveedor
    {
        return $this->pagoProveedor;
    }

    public function setPagoProveedor(
        ?PagoProveedor $pagoProveedor
    ): self {
        $this->pagoProveedor = $pagoProveedor;

        return $this;
    }
}