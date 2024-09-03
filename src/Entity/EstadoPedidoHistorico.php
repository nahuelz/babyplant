<?php

namespace App\Entity;

use App\Entity\EstadoPedido;
use App\Entity\Pedido;
use App\Entity\Traits\Auditoria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_pedido_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoPedidoHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Pedido::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_pedido", referencedColumnName="id", nullable=false)
     */
    protected $pedido;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoPedido::class)
     * @ORM\JoinColumn(name="id_estado_pedido", referencedColumnName="id", nullable=false)
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

    public function getId(): ?int {
        return $this->id;
    }

    public function getPedido(): ?Pedido {
        return $this->pedido;
    }

    public function setPedido(?Pedido $pedido): self {
        $this->pedido = $pedido;

        return $this;
    }

    public function getEstado(): ?EstadoPedido {
        return $this->estado;
    }

    public function setEstado(?EstadoPedido $estado): self {
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
}
