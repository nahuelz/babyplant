<?php

namespace App\Entity;

use App\Entity\Traits;
use App\Entity\Traits\Auditoria;
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









}