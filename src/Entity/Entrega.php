<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="entrega")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Entrega {

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
     * @ORM\OneToMany(targetEntity=EntregaProducto::class, mappedBy="entrega", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $entregasProductos;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class)
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class, inversedBy="entregas")
     * @ORM\JoinColumn(name="id_cliente_entrega", referencedColumnName="id")
     */
    private $clienteEntrega;

    /**
     * @ORM\ManyToOne(targetEntity=Remito::class, inversedBy="entregas", cascade={"all"}))
     * @ORM\JoinColumn(name="id_remito", referencedColumnName="id", nullable=true)
     */
    private $remito;

    /**
     * @ORM\OneToMany(targetEntity=EstadoEntregaHistorico::class, mappedBy="entrega", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoEntrega::class)
     * @ORM\JoinColumn(name="id_estado", referencedColumnName="id", nullable=true)
     */
    private mixed $estado;

    /**
     * @var string|null
     *
     * @ORM\Column(name="observacion", type="text", nullable=true)
     */
    private $observacion;

    /**
     * @param $historicoEstados
     */
    public function __construct()
    {
        $this->entregasProductos = new ArrayCollection();
        $this->historicoEstados = new ArrayCollection();
        $this->estado = null; // Inicializar la propiedad estado
    }

    public function __toString()
    {

        return 'Entrega N° '.$this->getId();
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
    }

    public function getRemito(): mixed
    {
        return $this->remito;
    }

    public function setRemito(mixed $remito): void
    {
        $this->remito = $remito;
    }

    /**
     * @return mixed
     */
    public function getEntregasProductos()
    {
        return $this->entregasProductos;
    }

    /**
     * @param mixed $entregasProductos
     */
    public function setEntregasProductos($entregasProductos): void
    {
        $this->entregasProductos = $entregasProductos;
    }

    public function getCodigo(){
        return str_pad($this->id, 6, "0", STR_PAD_LEFT);
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

    public function addHistoricoEstado(EstadoEntregaHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setEntrega($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoEntregaHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            // set the owning side to null (unless already changed)
            if ($historicoEstado->getEntrega() === $this) {
                $historicoEstado->setEntrega(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getClienteEntrega()
    {
        return $this->clienteEntrega;
    }

    /**
     * @param mixed $clienteEntrega
     */
    public function setClienteEntrega($clienteEntrega): void
    {
        $this->clienteEntrega = $clienteEntrega;
    }

    public function addEntregaProducto(EntregaProducto $entregaProducto): self {
        if (!$this->entregasProductos->contains($entregaProducto)) {
            $this->entregasProductos[] = $entregaProducto;
            $entregaProducto->setEntrega($this);
        }

        return $this;
    }

    public function removeEntregaProducto(EntregaProducto $entregaProducto): self {
        if ($this->entregasProductos->removeElement($entregaProducto)) {
            // set the owning side to null (unless already changed)
            if ($entregaProducto->getEntrega() === $this) {
                $entregaProducto->setEntrega(null);
            }
        }

        return $this;
    }

    public function getTotalSinDescuento(){
        $total = 0.00;
        foreach ($this->getEntregasProductos() as $entregaProducto) {
            $total += $entregaProducto->getPrecioSubTotal();
        }
        return ($total);
    }

    public function getTotalConDescuento(){
        $total = $this->getTotalSinDescuento();
        if ($this->getRemito()->getTipoDescuento() != null) {
            switch ($this->getRemito()->getTipoDescuento()->getCodigoInterno()) {
                case 1:
                    $total -= $this->getRemito()->getCantidadDescuento();
                    break;
                case 2:
                    $total -= (($total * $this->getRemito()->getCantidadDescuento()) / 100);
                    break;
            }
        }
        return $total;
    }

    public function getAdelanto(){
        $entregaProducto = $this->getEntregasProductos()->first();
        return ($entregaProducto->getEntrega()->getPedidoProducto()->getAdelanto());
    }

    public function getCuentaCorrientePedido(){
        return $this->getEntregasProductos()->first()->getCuentaCorrientePedido();
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function setObservacion(?string $observacion): void
    {
        $this->observacion = $observacion;
    }


}