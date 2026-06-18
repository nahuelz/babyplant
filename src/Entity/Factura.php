<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
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

    public function __construct()
    {
        $this->detalles = new ArrayCollection();
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

}