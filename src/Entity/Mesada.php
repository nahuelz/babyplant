<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use phpDocumentor\Reflection\Types\Integer;

/**
 * EntregaPedido
 *
 * @ORM\Table(name="mesada")
 * @ORM\Entity()
 */
class Mesada {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="decimal", precision=6, scale=1, nullable=false)
     */
    private $cantidadBandejas;

    /**
     * @ORM\Column(name="cantidad_bandejas_entregadas", type="decimal", precision=10, scale=1, nullable=true, options={"default": 0})
     */
    private $cantidadBandejasEntregadas;

    /**
     * @var TipoMesada
     *
     * @ORM\ManyToOne(targetEntity=TipoMesada::class, inversedBy="mesadas", cascade={"all"})
     * @ORM\JoinColumn(name="id_tipo_mesada", referencedColumnName="id", nullable=true)
     */
    protected $tipoMesada;

    /**
     * @ORM\OneToMany(targetEntity=EstadoMesadaHistorico::class, mappedBy="mesada", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoMesada::class)
     * @ORM\JoinColumn(name="id_estado_mesada", referencedColumnName="id", nullable=false)
     */
    private $estado;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class)
     * @ORM\JoinColumn(name="id_producto", referencedColumnName="id")
     */
    private $pedidoProducto;


    public function __construct()
    {
        $this->cantidadBandejasEntregadas = 0;
        $this->cantidadBandejas = 0;
        $this->historicoEstados = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getCantidadBandejas(): mixed
    {
        return $this->cantidadBandejas == (int)$this->cantidadBandejas 
            ? (int)$this->cantidadBandejas 
            : $this->cantidadBandejas;
    }

    public function setCantidadBandejas(mixed $cantidadBandejas): void
    {
        $this->cantidadBandejas = $cantidadBandejas;
    }

    public function getCantidadBandejasEntregadas(): mixed
    {
        return $this->cantidadBandejasEntregadas == (int)$this->cantidadBandejasEntregadas
            ? (int)$this->cantidadBandejasEntregadas
            : $this->cantidadBandejasEntregadas;
    }

    public function setCantidadBandejasEntregadas(mixed $cantidadBandejasEntregadas): void
    {
        $this->cantidadBandejasEntregadas = $cantidadBandejasEntregadas;
    }

    public function getTipoMesada()
    {
        return $this->tipoMesada;
    }

    public function setTipoMesada($tipoMesada): void
    {
        $this->tipoMesada = $tipoMesada;
    }

    public function getHistoricoEstados(): ArrayCollection
    {
        return $this->historicoEstados;
    }

    public function setHistoricoEstados(ArrayCollection $historicoEstados): void
    {
        $this->historicoEstados = $historicoEstados;
    }

    public function addHistoricoEstado(EstadoMesadaHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setMesada($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoMesadaHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            // set the owning side to null (unless already changed)
            if ($historicoEstado->getMesada() === $this) {
                $historicoEstado->setMesada(null);
            }
        }

        return $this;
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

    public function getCantidadBandejasTotal(){
        return $this->cantidadBandejas + $this->cantidadBandejasEntregadas;
    }

    public function entregarBandejas($cantidad){
        $this->cantidadBandejasEntregadas += $cantidad;
        $this->cantidadBandejas -= $cantidad;
        $this->getTipoMesada()->actualizarOcupado();
    }


}