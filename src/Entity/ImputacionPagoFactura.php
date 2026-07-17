<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;

/**
 * ImputacionPagoFactura
 *
 * Vincula un pago a proveedor con una factura e indica cuánto de ese pago
 * se imputa a la factura. El monto se expresa en la moneda de la factura.
 *
 * @ORM\Entity
 * @ORM\Table(name="imputacion_pago_factura")
 */
class ImputacionPagoFactura
{
    use Auditoria;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=PagoProveedor::class, inversedBy="imputaciones")
     * @ORM\JoinColumn(name="id_pago_proveedor", referencedColumnName="id", nullable=false)
     */
    private $pagoProveedor;

    /**
     * @ORM\ManyToOne(targetEntity=Factura::class, inversedBy="imputaciones")
     * @ORM\JoinColumn(name="id_factura", referencedColumnName="id", nullable=false)
     */
    private $factura;

    /**
     * Monto imputado, expresado en la moneda de la factura.
     *
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $monto = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPagoProveedor(): ?PagoProveedor
    {
        return $this->pagoProveedor;
    }

    public function setPagoProveedor(?PagoProveedor $pagoProveedor): self
    {
        $this->pagoProveedor = $pagoProveedor;
        return $this;
    }

    public function getFactura(): ?Factura
    {
        return $this->factura;
    }

    public function setFactura(?Factura $factura): self
    {
        $this->factura = $factura;
        return $this;
    }

    public function getMonto(): float
    {
        return (float) $this->monto;
    }

    public function setMonto($monto): self
    {
        $this->monto = (string) $monto;
        return $this;
    }
}
