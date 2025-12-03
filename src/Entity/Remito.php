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
     * @ORM\ManyToOne(targetEntity=TipoDescuento::class)
     * @ORM\JoinColumn(name="id_tipo_descuento", referencedColumnName="id", nullable=true)
     */
    private $tipoDescuento;

    /**
     * @ORM\Column(name="cantidad_descuento", type="integer", nullable=true)
     */
    private $cantidadDescuento;

    /**
     * @ORM\Column(name="motivo_descuento", type="string", length=255, nullable=true)
     */
    private $motivoDescuento;

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
     * @ORM\OrderBy({"fechaCreacion" = "ASC"})
     */
    private $pagos;

    /**
     * @ORM\OneToMany(targetEntity=Entrega::class, mappedBy="remito", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $entregas;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class, inversedBy="remitos")
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id", nullable=false)
     */
    private $cliente;

    /**
     * @ORM\Column(name="saldo_cuenta_corriente", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $saldoCuentaCorriente;

    /**
     * @ORM\Column(name="total_deuda", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalDeuda;

    public function __construct()
    {
        $this->entregas = new ArrayCollection();
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

    public function getTotalSinDescuento(){
        $total = 0.00;
        foreach ($this->getEntregas() as $entrega) {
            foreach ($entrega->getEntregasProductos() as $entregaProducto) {
                $total += $entregaProducto->getPrecioSubTotal();
            }
        }
        return $total;
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

    public function getMontoDescuento($totalSinDescuento = null){
        $totalSinDescuento = ($totalSinDescuento == null ? $this->getTotalSinDescuento() : $totalSinDescuento);
        $descuento = '';
        if ($this->getTipoDescuento() != null) {
            switch ($this->getTipoDescuento()->getCodigoInterno()) {
                case 1:
                    $descuento = $this->getCantidadDescuento();
                    break;
                case 2:
                    $descuento = (($totalSinDescuento * $this->getCantidadDescuento()) / 100);
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
        /*if ($this->getTipoDescuento() != null) {
            if ($this->getTipoDescuento()->getCodigoInterno() == 1) {
                $cantidadDescuento = $this->getCantidadDescuento();
                foreach ($this->getEntregas() as $entrega) {
                    foreach ($entrega->getEntregasProductos() as $entregaProducto) {
                        if ($cantidadDescuento > 0) {
                            $descontar = $cantidadDescuento >= $entregaProducto->getPrecioSubTotal() ? $entregaProducto->getPrecioSubTotal() : $cantidadDescuento;
                            $entregaProducto->setCantidadDescuentoFijo($descontar);
                            $cantidadDescuento -= $descontar;
                        }
                    }
                }
            }
        }*/
    }

    /**
     * @return string|null
     */
    public function getMotivoDescuento(): ?string
    {
        return $this->motivoDescuento;
    }

    /**
     * @param string|null $motivoDescuento
     * @return $this
     */
    public function setMotivoDescuento(?string $motivoDescuento): self
    {
        $this->motivoDescuento = $motivoDescuento;
        return $this;
    }

    /**
     * @return Collection|EstadoRemitoHistorico[]
     */
    public function getHistoricoEstados(): Collection
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

    public function addEntrega(Entrega $entrega): self {
        if (!$this->entregas->contains($entrega)) {
            $this->entregas[] = $entrega;
            $entrega->setRemito($this);
        }

        return $this;
    }

    public function removeEntrega(Entrega $entrega): self {
        if ($this->entregas->removeElement($entrega)) {
            // set the owning side to null (unless already changed)
            if ($entrega->getRemito() === $this) {
                $entrega->setRemito(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntregas()
    {
        return $this->entregas;
    }

    public function getAdelanto()
    {
        $adelanto = 0;
        $pedidosVistos = [];

        foreach ($this->entregas as $entrega) {
            foreach ($entrega->getEntregasProductos() as $entregaProducto) {
                $pedido = $entregaProducto->getPedidoProducto()->getPedido();
                $pedidoId = $pedido->getId();

                if (!isset($pedidosVistos[$pedidoId])) {
                    $adelanto += $pedido->getAdelanto();
                    $pedidosVistos[$pedidoId] = true;
                }
            }
        }

        return $adelanto;
    }

    public function getAdelantoReserva()
    {
        $adelanto = 0;
        $reservasVistas = [];

        foreach ($this->entregas as $entrega) {
            if ($entrega->getReserva() != null) {
                $reserva = $entrega->getReserva();
                $reservaId = $reserva->getId();
                if (!isset($reservasVistas[$reservaId])) {
                    $adelanto += $reserva->getAdelanto();
                    $reservasVistas[$reservaId] = true;
                }
            }
        }

        return $adelanto;
    }

    public function getDescuentoTipoDescuento(){
        return  ($this->getTipoDescuento() . ' ' . $this->getCantidadDescuento());
    }

    public function getCuentaCorrientePedido(){
        return $this->getEntregas()->first()->getCuentaCorrientePedido();
    }

    /**
     * @return mixed
     */
    public function getSaldoCuentaCorriente()
    {
        return $this->saldoCuentaCorriente;
    }

    /**
     * @param mixed $saldoCuentaCorriente
     */
    public function setSaldoCuentaCorriente($saldoCuentaCorriente): void
    {
        $this->saldoCuentaCorriente = $saldoCuentaCorriente;
    }

    /**
     * @return mixed
     */
    public function getTotalDeuda()
    {
        return $this->totalDeuda;
    }

    /**
     * @param mixed $totalDeuda
     */
    public function setTotalDeuda($totalDeuda): void
    {
        $this->totalDeuda = $totalDeuda;
    }



}