<?php

namespace App\Entity;

use App\Entity\EstadoPedidoProducto;
use App\Entity\PedidoProducto;
use App\Entity\Traits\Auditoria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="estado_pedido_producto_historico")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EstadoPedidoProductoHistorico {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class, inversedBy="historicoEstados")
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id", nullable=false)
     */
    protected $pedidoProducto;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoPedidoProducto::class)
     * @ORM\JoinColumn(name="id_estado_pedido_producto", referencedColumnName="id", nullable=false)
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
     * @ORM\OneToOne(targetEntity=DatosEntrega::class)
     * @ORM\JoinColumn(name="id_entrega_producto", referencedColumnName="id", nullable=true)
     */
    private $DatosEntrega;

    /**
     * @ORM\ManyToOne(targetEntity=Reserva::class)
     * @ORM\JoinColumn(name="id_reserva", referencedColumnName="id", nullable=true)
     */
    private $reserva;

    public function getId(): ?int {
        return $this->id;
    }

    public function getPedidoProducto(): ?PedidoProducto {
        return $this->pedidoProducto;
    }

    public function setPedidoProducto(?PedidoProducto $pedidoProducto): self {
        $this->pedidoProducto = $pedidoProducto;

        return $this;
    }

    public function getEstado(): ?EstadoPedidoProducto {
        return $this->estado;
    }

    public function setEstado(?EstadoPedidoProducto $estado): self {
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
    public function getDatosEntrega()
    {
        return $this->DatosEntrega;
    }

    /**
     * @param mixed $DatosEntrega
     */
    public function setDatosEntrega($DatosEntrega): void
    {
        $this->DatosEntrega = $DatosEntrega;
    }

    /**
     * @return mixed
     */
    public function getReserva()
    {
        return $this->reserva;
    }

    /**
     * @param mixed $reserva
     */
    public function setReserva($reserva): void
    {
        $this->reserva = $reserva;
    }




}
