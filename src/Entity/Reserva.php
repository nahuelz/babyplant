<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="reserva")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Reserva {

    use Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class, inversedBy="reservas")
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id")
    */
    private $pedidoProducto;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class, inversedBy="reservas")
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

    /**
     * @ORM\ManyToOne(targetEntity=Entrega::class)
     * @ORM\JoinColumn(name="id_entrega", referencedColumnName="id", nullable=true)
     */
    private $entrega;

    /**
     * @ORM\OneToMany(targetEntity=EstadoReservaHistorico::class, mappedBy="reserva", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoReserva::class)
     * @ORM\JoinColumn(name="id_estado", referencedColumnName="id", nullable=true)
     */
    private mixed $estado;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="string", length=50, nullable=false)
     */
    private $cantidadBandejas;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $fechaEntregaEstimada;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $fechaEntregaReal;

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
    }

    public function __toString()
    {

        return 'Reserva N° '.$this->getId();
    }


    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCliente()
    {
        return $this->cliente;
    }

    /**
     * @param mixed $cliente
     */
    public function setCliente($cliente): void
    {
        $this->cliente = $cliente;
        $cliente->addReserva($this);
    }
    /**
     * @return mixed
     */
    public function getHistoricoEstados()
    {
        return $this->historicoEstados;
    }

    /**
     * @param mixed $historicoEstados
     */
    public function setHistoricoEstados($historicoEstados): void
    {
        $this->historicoEstados = $historicoEstados;
    }

    public function getEstado(): mixed
    {
        return $this->estado;
    }

    public function setEstado(mixed $estado): void
    {
        $this->estado = $estado;
    }

    public function addHistoricoEstado(EstadoReservaHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setReserva($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoReservaHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            if ($historicoEstado->getReserva() === $this) {
                $historicoEstado->setReserva(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPedidoProducto()
    {
        return $this->pedidoProducto;
    }

    /**
     * @param mixed $pedidoProducto
     */
    public function setPedidoProducto($pedidoProducto): void
    {
        $this->pedidoProducto = $pedidoProducto;
    }

    /**
     * @return mixed
     */
    public function getEntrega()
    {
        return $this->entrega;
    }

    /**
     * @param mixed $entrega
     */
    public function setEntrega($entrega): void
    {
        $this->entrega = $entrega;
    }

    /**
     * @return mixed
     */
    public function getCantidadBandejas()
    {
        return $this->cantidadBandejas;
    }

    /**
     * @param mixed $cantidadBandejas
     */
    public function setCantidadBandejas($cantidadBandejas): void
    {
        $this->cantidadBandejas = $cantidadBandejas;
    }

    /**
     * @return mixed
     */
    public function getFechaEntregaEstimada()
    {
        return $this->fechaEntregaEstimada;
    }

    /**
     * @param mixed $fechaEntregaEstimada
     */
    public function setFechaEntregaEstimada($fechaEntregaEstimada): void
    {
        $this->fechaEntregaEstimada = $fechaEntregaEstimada;
        $this->fechaEntregaReal = $fechaEntregaEstimada;
    }

    /**
     * @return mixed
     */
    public function getFechaEntregaReal()
    {
        return $this->fechaEntregaReal;
    }

    /**
     * @param mixed $fechaEntregaReal
     */
    public function setFechaEntregaReal($fechaEntregaReal): void
    {
        $this->fechaEntregaReal = $fechaEntregaReal;
    }






}