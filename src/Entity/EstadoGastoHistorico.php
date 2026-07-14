<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_gasto_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoGastoHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Gasto::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_gasto", referencedColumnName="id", nullable=true)
     */
    protected $gasto;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoGasto::class)
     * @ORM\JoinColumn(name="id_estado_gasto", referencedColumnName="id", nullable=false)
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

    public function getGasto(): ?Gasto {
        return $this->gasto;
    }

    public function setGasto(?Gasto $gasto): self {
        $this->gasto = $gasto;

        return $this;
    }

    public function getEstado(): ?EstadoGasto {
        return $this->estado;
    }

    public function setEstado(?EstadoGasto $estado): self {
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
