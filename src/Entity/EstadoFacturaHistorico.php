<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_factura_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoFacturaHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Factura::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_factura", referencedColumnName="id", nullable=true)
     */
    protected $factura;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoFactura::class)
     * @ORM\JoinColumn(name="id_estado_factura", referencedColumnName="id", nullable=false)
     */
    private $estado;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $fecha;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $motivo;

    /**
     * @ORM\ManyToOne(targetEntity=PagoProveedor::class)
     * @ORM\JoinColumn(name="id_pago_proveedor", referencedColumnName="id", nullable=true)
     */
    private $pagoProveedor;

    public function getId(): ?int {
        return $this->id;
    }

    public function getFactura(): ?Factura {
        return $this->factura;
    }

    public function setFactura(?Factura $factura): self {
        $this->factura = $factura;

        return $this;
    }

    public function getEstado(): ?EstadoFactura {
        return $this->estado;
    }

    public function setEstado(?EstadoFactura $estado): self {
        $this->estado = $estado;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface {
        return $this->fecha;
    }

    public function setFecha(\DateTimeInterface $fecha): self {
        $this->fecha = $fecha;

        return $this;
    }

    public function getMotivo(): ?string {
        return $this->motivo;
    }

    public function setMotivo(?string $motivo): self
    {
        $this->motivo = $motivo;

        return $this;
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
}
