<?php

namespace App\Entity;

use App\Entity\Traits;
use App\Repository\PedidoRepository;
use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="pedido")
 * @ORM\Entity(repositoryClass=PedidoRepository::class)
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
     * @ORM\ManyToOne(targetEntity=Usuario::class, inversedBy="pedidos")
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

    /**
     * @ORM\Column(name="observacion", type="string", length=255, nullable=true)
     */
    private $observacion;

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
        $this->pedidosProductos = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Pedido N° '.$this->getId();
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

    public function addPedidosProducto(PedidoProducto $pedidosProducto): static
    {
        if (!$this->pedidosProductos->contains($pedidosProducto)) {
            $this->pedidosProductos->add($pedidosProducto);
            $pedidosProducto->setPedido($this);
        }

        return $this;
    }

    public function removePedidosProducto(PedidoProducto $pedidosProducto): static
    {
        if ($this->pedidosProductos->removeElement($pedidosProducto)) {
            // set the owning side to null (unless already changed)
            if ($pedidosProducto->getPedido() === $this) {
                $pedidosProducto->setPedido(null);
            }
        }

        return $this;
    }

}