<?php

namespace App\Entity;

use App\Entity\Traits;
use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="pedido")
 * @ORM\Entity
 *
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Pedido {

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
     * @ORM\OneToMany(targetEntity=PedidoProducto::class, mappedBy="pedido", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $pedidosProductos;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class)
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

    /**
     * @ORM\OneToMany(targetEntity=EstadoPedidoHistorico::class, mappedBy="matriculado", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoPedido::class)
     * @ORM\JoinColumn(name="id_estado_pedido", referencedColumnName="id", nullable=false)
     */
    private $estado;

    /**
     * @ORM\Column(name="observacion", type="string", length=255, nullable=true)
     */
    private $observacion;

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Pedido '.$this->getId();
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPedidosProductos()
    {
        return $this->pedidosProductos;
    }

    /**
     * @param mixed $pedidosProductos
     */
    public function setPedidosProductos($pedidosProductos): void
    {
        $this->pedidosProductos = $pedidosProductos;

        foreach ($pedidosProductos as $pedidoProducto){
            $pedidoProducto->setPedido($this);
        }
    }

    /**
     * @return mixed
     */
    public function addPedidosProductos($pedidoProducto)
    {
        if (!$this->pedidosProductos->contains($pedidoProducto)) {
            $this->pedidosProductos[] = $pedidoProducto;
            $pedidoProducto->setPedido($this);
        }

        return $this;
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

    /**
     * @return mixed
     */
    public function getEstado()
    {
        return $this->estado;
    }

    /**
     * @param mixed $estado
     */
    public function setEstado($estado): void
    {
        $this->estado = $estado;
    }

    public function addHistoricoEstado(EstadoPedidoHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setPedido($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoPedidoHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            // set the owning side to null (unless already changed)
            if ($historicoEstado->getPedido() === $this) {
                $historicoEstado->setPedido(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getObservacion()
    {
        return $this->observacion;
    }

    /**
     * @param mixed $observacion
     */
    public function setObservacion($observacion): void
    {
        $this->observacion = $observacion;
    }


}