<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="factura_detalle")
 */
class FacturaDetalle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Factura::class, inversedBy="detalles")
     * @ORM\JoinColumn(name="id_factura", referencedColumnName="id", nullable=false)
     */
    private $factura;

    /**
     * @ORM\ManyToOne(targetEntity=TipoConcepto::class)
     * @ORM\JoinColumn(name="id_tipo_concepto", referencedColumnName="id", nullable=false)
     */
    private $concepto;

    /**
     * @ORM\ManyToOne(targetEntity=TipoSubConcepto::class)
     * @ORM\JoinColumn(name="id_tipo_sub_concepto", referencedColumnName="id", nullable=true)
     */
    private $subConcepto;

    /**
     * @ORM\ManyToOne(targetEntity=TipoGrupo::class)
     * @ORM\JoinColumn(name="id_tipo_grupo", referencedColumnName="id", nullable=true)
     */
    private $tipoGrupo;

    /**
     * @ORM\Column(name="cantidad", type="integer", nullable=true)
     */
    private $cantidad;

    /**
     * @ORM\Column(name="precio_unitario", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $precioUnitario;

    /**
     * @ORM\Column(name="descripcion", type="string", length=255, nullable=true)
     */
    private $descripcion;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getConcepto(): ?TipoConcepto
    {
        return $this->concepto;
    }

    public function setConcepto(?TipoConcepto $concepto): self
    {
        $this->concepto = $concepto;
        return $this;
    }

    public function getSubConcepto(): ?TipoSubConcepto
    {
        return $this->subConcepto;
    }

    public function setSubConcepto(?TipoSubConcepto $subConcepto): self
    {
        $this->subConcepto = $subConcepto;
        return $this;
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

    public function getMontoTotal(): ?string
    {
        return (string) ((float) $this->cantidad * (float) $this->precioUnitario);
    }

    public function getPrecioUnitario(): ?string
    {
        return $this->precioUnitario;
    }

    public function setPrecioUnitario($precioUnitario): self
    {
        $this->precioUnitario = $precioUnitario;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function __toString(): string
    {
        $nombre = $this->concepto ? $this->concepto->getNombre() : 'Concepto';
        if ($this->subConcepto) {
            $nombre .= ' - ' . $this->subConcepto->getNombre();
        }
        return $nombre . ' ($' . $this->getMontoTotal() . ')';
    }

    /**
     * @return mixed
     */
    public function getCantidad()
    {
        return $this->cantidad;
    }

    /**
     * @param mixed $cantidad
     */
    public function setCantidad($cantidad): void
    {
        $this->cantidad = $cantidad;
    }


}
