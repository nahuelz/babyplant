<?php

namespace App\Entity;

use App\Entity\Traits;
use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="remito")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Remito {

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
     * @ORM\OneToMany(targetEntity=RemitoProducto::class, mappedBy="remito", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $remitosProductos;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class, inversedBy="remitos")
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id")
     */
    private $cliente;

    /**
     * @ORM\Column(name="fecha_remito", type="datetime", nullable=true)
     */
    private $fechaRemito;

    /**
     * @ORM\ManyToOne(targetEntity=TipoDescuento::class)
     * @ORM\JoinColumn(name="id_tipo_descuento", referencedColumnName="id", nullable=true)
     */
    private $tipoDescuento;

    /**
     * @ORM\Column(name="cantidad_descuento", type="integer", nullable=true)
     */
    private $cantidadDescuento;

    /**
     * @ORM\OneToMany(targetEntity=EstadoRemitoHistorico::class, mappedBy="remito", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoRemito::class)
     * @ORM\JoinColumn(name="id_estado_remito", referencedColumnName="id", nullable=false)
     */
    private mixed $estado;


    /**
     * @ORM\OneToMany(targetEntity=Pago::class, mappedBy="remito", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $pagos;

    public function __construct()
    {
        $this->remitosProductos = new ArrayCollection();
        $this->historicoEstados = new ArrayCollection();
        $this->pagos = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Remito N° '.$this->getId();
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getCodigo(){
        return str_pad($this->id, 6, "0", STR_PAD_LEFT);
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
    public function getRemitosProductos()
    {
        return $this->remitosProductos;
    }

    /**
     * @param mixed $remitosProductos
     */
    public function setRemitosProductos($remitosProductos): void
    {
        $this->remitosProductos = $remitosProductos;

        foreach ($remitosProductos as $remitoProducto){
            $remitoProducto->setRemito($this);
        }
    }

    /**
     * @return mixed
     */
    public function addRemitosProductos($remitoProducto)
    {
        if (!$this->remitosProductos->contains($remitoProducto)) {
            $this->remitosProductos[] = $remitoProducto;
            $remitoProducto->setRemito($this);
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

    public function removeRemitosProducto(RemitoProducto $remitosProducto): static
    {
        if ($this->remitosProductos->removeElement($remitosProducto)) {
            // set the owning side to null (unless already changed)
            if ($remitosProducto->getRemito() === $this) {
                $remitosProducto->setRemito(null);
            }
        }

        return $this;
    }

    public function getTotalSinDescuento(){
        $total = 0.00;
        foreach ($this->remitosProductos as $remitoProducto){
            $total += $remitoProducto->getPrecioSubTotal();
        }
        return ($total);
    }

    public function getTotalConDescuento(){
        $total = $this->getTotalSinDescuento();
        if ($this->getTipoDescuento() != null) {
            switch ($this->getTipoDescuento()->getCodigoInterno()) {
                case 1:
                    $total -= $this->getCantidadDescuento();
                    break;
                case 2:
                    $total -= (($total * $this->getCantidadDescuento()) / 100);
                    break;
            }
        }
        return $total;
    }

    public function getMontoDescuento(){
        $descuento = '';
        if ($this->getTipoDescuento() != null) {
            switch ($this->getTipoDescuento()->getCodigoInterno()) {
                case 1:
                    $descuento = $this->getCantidadDescuento();
                    break;
                case 2:
                    $descuento = (($this->getTotalSinDescuento() * $this->getCantidadDescuento()) / 100);
                    break;
            }
        }

        return $descuento;
    }

    public function getTipoDescuentoString(){
        $tipoDescuento = '';
        if ($this->getTipoDescuento() != null) {
            switch ($this->getTipoDescuento()->getCodigoInterno()) {
                case 1:
                    $tipoDescuento = '-';
                    break;
                case 2:
                    $tipoDescuento = '('.$this->getCantidadDescuento().'%) -';
                    break;
            }
        }

        return $tipoDescuento;
    }

    public function getFechaRemito(): mixed
    {
        return $this->fechaRemito;
    }

    public function setFechaRemito(mixed $fechaRemito): void
    {
        $this->fechaRemito = $fechaRemito;
    }

    /**
     * @return mixed
     */
    public function getTipoDescuento()
    {
        return $this->tipoDescuento;
    }

    /**
     * @param mixed $tipoDescuento
     */
    public function setTipoDescuento($tipoDescuento): void
    {
        $this->tipoDescuento = $tipoDescuento;
    }

    /**
     * @return mixed
     */
    public function getCantidadDescuento()
    {
        return $this->cantidadDescuento;
    }

    /**
     * @param mixed $cantidadDescuento
     */
    public function setCantidadDescuento($cantidadDescuento): void
    {
        $this->cantidadDescuento = $cantidadDescuento;
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

    public function addHistoricoEstado(EstadoRemitoHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setRemito($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoRemitoHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            // set the owning side to null (unless already changed)
            if ($historicoEstado->getRemito() === $this) {
                $historicoEstado->setRemito(null);
            }
        }

        return $this;
    }

    public function getPagos()
    {
        return $this->pagos;
    }

    public function setPagos(ArrayCollection $pagos): void
    {
        $this->pagos = $pagos;
    }

    /**
     * @return mixed
     */
    public function addPago(Pago $pago)
    {
        if (!$this->pagos->contains($pago)) {
            $this->pagos[] = $pago;
            $pago->setRemito($this);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPendiente()
    {
        $pendiente = $this->getTotalConDescuento();
        foreach ($this->pagos as $pago) {
            $pendiente -=$pago->getMonto();
        }
        return $pendiente;
    }
}