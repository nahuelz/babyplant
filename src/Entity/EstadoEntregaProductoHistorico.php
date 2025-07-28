<?php

namespace App\Entity;

use App\Entity\EstadoEntregaProducto;
use App\Entity\EntregaProducto;
use App\Entity\Traits\Auditoria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_entrega_producto_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoEntregaProductoHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=EntregaProducto::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_entrega_producto", referencedColumnName="id", nullable=false)
     */
    protected $entregaProducto;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoEntregaProducto::class)
     * @ORM\JoinColumn(name="id_estado_entrega_producto", referencedColumnName="id", nullable=false)
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
     * @ORM\OneToOne(targetEntity=Pago::class)
     * @ORM\JoinColumn(name="id_pago", referencedColumnName="id")
     */
    private $pago;

    public function getId(): ?int {
        return $this->id;
    }

    public function getEntregaProducto(): ?EntregaProducto {
        return $this->entregaProducto;
    }

    public function setEntregaProducto(?EntregaProducto $entregaProducto): self {
        $this->entregaProducto = $entregaProducto;

        return $this;
    }

    public function getEstado(): ?EstadoEntregaProducto {
        return $this->estado;
    }

    public function setEstado(?EstadoEntregaProducto $estado): self {
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

    public function setMotivo(string $motivo): self
    {
        $this->motivo = $motivo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPago()
    {
        return $this->pago;
    }

    /**
     * @param mixed $pago
     */
    public function setPago($pago): void
    {
        $this->pago = $pago;
    }


}
