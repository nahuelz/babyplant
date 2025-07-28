<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="entrega_producto")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class EntregaProducto {

    use Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Entrega::class, inversedBy="entregasProductos")
     * @ORM\JoinColumn(name="id_entrega", referencedColumnName="id", nullable=true)
     */
    private mixed $entrega;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class, inversedBy="entregasProductos")
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $pedidoProducto;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="string", length=50, nullable=false)
     */
    private $cantidadBandejas;

    /**
     * @ORM\Column(name="precio_unitario", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $precioUnitario;

    /**
     * @ORM\Column(name="monto_pendiente", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $montoPendiente;

    /**
     * @ORM\OneToMany(targetEntity=EstadoEntregaProductoHistorico::class, mappedBy="entregaProducto", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoEntregaProducto::class)
     * @ORM\JoinColumn(name="id_estado_entrega_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $estado;

    /**
     * @param $historicoEstados
     */
    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEntrega(): mixed
    {
        return $this->entrega;
    }

    public function setEntrega(mixed $entrega): void
    {
        $this->entrega = $entrega;
    }

    public function getCantidadBandejas()
    {
        return $this->cantidadBandejas;
    }

    public function setCantidadBandejas(mixed $cantidadBandejas): void
    {
        $this->cantidadBandejas = $cantidadBandejas;
    }

    public function getPedidoProducto()
    {
        return $this->pedidoProducto;
    }

    public function setPedidoProducto(mixed $pedidoProducto): void
    {
        $this->pedidoProducto = $pedidoProducto;
    }

    /**
     * @return mixed
     */
    public function getPrecioUnitario()
    {
        return $this->precioUnitario;
    }

    /**
     * @param mixed $precioUnitario
     */
    public function setPrecioUnitario($precioUnitario): void
    {
        $this->precioUnitario = $precioUnitario;
    }

    public function getPrecioSubTotal(){
        return $this->precioUnitario * $this->cantidadBandejas;
    }

    public function getCuentaCorrientePedido(){
        return $this->getPedidoProducto()->getCuentaCorrientePedido();
    }

    public function getMontoTotal(){
        return $this->precioUnitario * $this->cantidadBandejas;
    }

    /**
     * @return mixed
     */
    public function getMontoPendiente()
    {
        return $this->montoPendiente;
    }

    /**
     * @param mixed $montoPendiente
     */
    public function setMontoPendiente($montoPendiente): void
    {
        $this->montoPendiente = $montoPendiente;
    }

    public function actualizarMontoPendiente(){
        $this->montoPendiente = $this->getCantidadBandejas() * $this->getPrecioUnitario();
    }
    public function descontarMontoPendiente($monto){
        $this->montoPendiente -= $monto;
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

    public function addHistoricoEstado(EstadoEntregaProductoHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setEntregaProducto($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoEntregaProductoHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            // set the owning side to null (unless already changed)
            if ($historicoEstado->getEntregaProducto() === $this) {
                $historicoEstado->setEntregaProducto(null);
            }
        }

        return $this;
    }


}