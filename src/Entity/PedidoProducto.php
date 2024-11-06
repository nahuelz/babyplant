<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Repository\PedidoProductoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Grupo
 *
 * @ORM\Table(name="pedido_producto")
 * @ORM\Entity(repositoryClass=PedidoProductoRepository::class)
 *
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class PedidoProducto {

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
     * @ORM\ManyToOne(targetEntity=Pedido::class, inversedBy="pedidosProductos")
     * @ORM\JoinColumn(name="id_pedido", referencedColumnName="id", nullable=true)
     */
    private $pedido;

    /**
     * @ORM\ManyToOne(targetEntity=TipoVariedad::class)
     * @ORM\JoinColumn(name="id_tipo_variedad", referencedColumnName="id", nullable=true)
     */
    private $tipoVariedad;

    /**
     * @ORM\ManyToOne(targetEntity=TipoBandeja::class)
     * @ORM\JoinColumn(name="id_tipo_bandeja", referencedColumnName="id", nullable=true)
     */
    private $tipoBandeja;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="string", length=50, nullable=true)
     */
    private $cantBandejas;

    /**
     * @ORM\Column(name="cantidad_bandejas_reales", type="string", length=50, nullable=true)
     */
    private $cantBandejasReales;

    /**
     * @ORM\Column(name="cantidad_semillas", type="string", length=50, nullable=true)
     */
    private $cantSemillas;

    /**
     * @ORM\Column(name="fecha_siembra", type="datetime", nullable=true)
     */
    private $fechaSiembra;

    /**
     * @ORM\Column(name="fecha_entrega", type="datetime", nullable=true)
     */
    private $fechaEntrega;

    /**
     * @ORM\Column(name="fecha_entrega_pedido", type="datetime", nullable=true)
     */
    private $fechaEntregaPedido;

    /**
     * @ORM\Column(name="fecha_entrega_real", type="datetime", nullable=true)
     */
    private $fechaEntregaReal;

    /**
     * @ORM\Column(name="fecha_siembra_pedido", type="datetime", nullable=true)
     */
    private $fechaSiembraPedido;

    /**
     * @ORM\Column(name="fecha_pedido", type="datetime", nullable=true)
     */
    private $fechaPedido;

    /**
     * @ORM\Column(name="cantidad_dias_produccion", type="string", length=50, nullable=true)
     */
    private $cantDiasProduccion;

    /**
     * @ORM\ManyToOne(targetEntity=TipoOrigenSemilla::class)
     * @ORM\JoinColumn(name="id_tipo_origen_semilla", referencedColumnName="id", nullable=true)
     */
    private $tipoOrigenSemilla;

    /**
     * @ORM\Column(name="otro_origen_semilla", type="string", length=50, nullable=true)
     */
    private $otroOrigenSemilla;

    /**
     * @ORM\OneToMany(targetEntity=EstadoPedidoProductoHistorico::class, mappedBy="pedidoProducto", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoPedidoProducto::class)
     * @ORM\JoinColumn(name="id_estado_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private $estado;

    /**
     * @ORM\Column(name="codigo_sobre", type="string", length=50, nullable=true)
     */
    private $codigoSobre;

    /**
     * @ORM\Column(name="observacion", type="string", length=255, nullable=true)
     */
    private $observacion;

    /**
     * @ORM\Column(name="hora_siembra", type="datetime", length=255, nullable=true)
     */
    private $horaSiembra;

    /**
     * @ORM\Column(name="fecha_ingreso_camara", type="datetime", length=255, nullable=true)
     */
    private $fechaIngresoCamara;

    /**
     * @ORM\Column(name="fecha_salida_camara_estimada", type="datetime", length=255, nullable=true)
     */
    private $fechaSalidaCamaraEstimada;

    /**
     * @ORM\Column(name="fecha_salida_camara_real", type="datetime", length=255, nullable=true)
     */
    private $fechaSalidaCamaraReal;

    /**
     * @ORM\Column(name="numero_orden", type="integer", nullable=true)
     */
    private $numeroOrden;

    /**
     * @ORM\OneToMany(targetEntity=PedidoProductoMesada::class, mappedBy="pedidoProducto", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $mesadas;


    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
    }

    public function getNombreCompleto(){
        return $this->getTipoVariedad()->getNombreCompleto();
    }


    /**
     * @return int
     */
    public function getId(): int
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
    public function getPedido()
    {
        return $this->pedido;
    }

    /**
     * @param mixed $pedido
     */
    public function setPedido($pedido): void
    {
        $this->pedido = $pedido;
    }

    /**
     * @return mixed
     */
    public function getTipoVariedad()
    {
        return $this->tipoVariedad;
    }

    /**
     * @param mixed $tipoVariedad
     */
    public function setTipoVariedad($tipoVariedad): void
    {
        $this->tipoVariedad = $tipoVariedad;
    }

    /**
     * @return mixed
     */
    public function getTipoBandeja()
    {
        return $this->tipoBandeja;
    }

    /**
     * @param mixed $tipoBandeja
     */
    public function setTipoBandeja($tipoBandeja): void
    {
        $this->tipoBandeja = $tipoBandeja;
    }

    /**
     * @return mixed
     */
    public function getCantBandejas()
    {
        return $this->cantBandejas;
    }

    /**
     * @param mixed $cantBandejas
     */
    public function setCantBandejas($cantBandejas): void
    {
        $this->cantBandejas = $cantBandejas;
    }

    /**
     * @return mixed
     */
    public function getCantSemillas()
    {
        return $this->cantSemillas;
    }

    /**
     * @param mixed $cantSemillas
     */
    public function setCantSemillas($cantSemillas): void
    {
        $this->cantSemillas = $cantSemillas;
    }

    /**
     * @return mixed
     */
    public function getFechaSiembra()
    {
        return $this->fechaSiembra;
    }

    /**
     * @param mixed $fechaSiembra
     */
    public function setFechaSiembra($fechaSiembra): void
    {
        $this->fechaSiembra = $fechaSiembra;
    }

    /**
     * @return mixed
     */
    public function getFechaEntrega()
    {
        return $this->fechaEntrega;
    }

    /**
     * @param mixed $fechaEntrega
     */
    public function setFechaEntrega($fechaEntrega): void
    {
        $this->fechaEntrega = $fechaEntrega;
    }

    /**
     * @return mixed
     */
    public function getFechaPedido()
    {
        return $this->fechaPedido;
    }

    /**
     * @param mixed $fechaPedido
     */
    public function setFechaPedido($fechaPedido): void
    {
        $this->fechaPedido = $fechaPedido;
    }

    /**
     * @return mixed
     */
    public function getCantDiasProduccion()
    {
        return $this->cantDiasProduccion;
    }

    /**
     * @param mixed $cantDiasProduccion
     */
    public function setCantDiasProduccion($cantDiasProduccion): void
    {
        $this->cantDiasProduccion = $cantDiasProduccion;
    }

    /**
     * @return mixed
     */
    public function getTipoOrigenSemilla()
    {
        return $this->tipoOrigenSemilla;
    }

    /**
     * @param mixed $tipoOrigenSemilla
     */
    public function setTipoOrigenSemilla($tipoOrigenSemilla): void
    {
        $this->tipoOrigenSemilla = $tipoOrigenSemilla;
    }

    /**
     * @return mixed
     */
    public function getOtroOrigenSemilla()
    {
        return $this->otroOrigenSemilla;
    }

    /**
     * @param mixed $otroOrigenSemilla
     */
    public function setOtroOrigenSemilla($otroOrigenSemilla): void
    {
        $this->otroOrigenSemilla = $otroOrigenSemilla;
    }

    public function addHistoricoEstado(EstadoPedidoProductoHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setPedidoProducto($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoPedidoProductoHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            // set the owning side to null (unless already changed)
            if ($historicoEstado->getPedidoProducto() === $this) {
                $historicoEstado->setPedidoProducto(null);
            }
        }

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getHistoricoEstados()
    {
        return $this->historicoEstados;
    }

    /**
     * @param ArrayCollection $historicoEstados
     */
    public function setHistoricoEstados(ArrayCollection $historicoEstados): void
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
    public function getCodigoSobre()
    {
        return $this->codigoSobre;
    }

    /**
     * @param mixed $codigoSobre
     */
    public function setCodigoSobre($codigoSobre): void
    {
        $this->codigoSobre = $codigoSobre;
    }

    /**
     * @return mixed
     */
    public function getFechaSiembraPedido()
    {
        return $this->fechaSiembraPedido;
    }

    /**
     * @param mixed $fechaSiembraPedido
     */
    public function setFechaSiembraPedido($fechaSiembraPedido): void
    {
        $this->fechaSiembraPedido = $fechaSiembraPedido;
    }

    /**
     * @return mixed
     */
    public function getFechaEntregaPedido()
    {
        return $this->fechaEntregaPedido;
    }

    /**
     * @param mixed $fechaEntregaPedido
     */
    public function setFechaEntregaPedido($fechaEntregaPedido): void
    {
        $this->fechaEntregaPedido = $fechaEntregaPedido;
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

    /**
     * @return mixed
     */
    public function getCantBandejasReales()
    {
        return $this->cantBandejasReales;
    }

    /**
     * @param mixed $cantBandejasReales
     */
    public function setCantBandejasReales($cantBandejasReales): void
    {
        $this->cantBandejasReales = $cantBandejasReales;
    }

    /**
     * @return mixed
     */
    public function getHoraSiembra()
    {
        return $this->horaSiembra;
    }

    /**
     * @param mixed $horaSiembra
     */
    public function setHoraSiembra($horaSiembra): void
    {
        if ($horaSiembra != null) {
            $fecha = $this->getFechaSiembra();
            $hora = intval(substr($horaSiembra, 0, 2));
            $minutos = intval(substr($horaSiembra, 3, 2));
            $fecha->setTime($hora, $minutos);
            $this->horaSiembra = $fecha;
        }else{
            $this->horaSiembra = new \DateTime();
        }
    }

    /**
     * @return mixed
     */
    public function getNumeroOrden()
    {
        return $this->numeroOrden;
    }

    /**
     * @param mixed $numeroOrden
     */
    public function setNumeroOrden($numeroOrden): void
    {
        $this->numeroOrden = $numeroOrden;
    }

    public function getTipoProducto(){
        return $this->getTipoVariedad()->getTipoSubProducto()->getTipoProducto();
    }

    public function getNumeroOrdenCompleto(){
        return ($this->numeroOrden . ' ' . strtoupper(substr($this->getTipoProducto(), 0, 3)));
    }

    /**
     * @return mixed
     */
    public function getFechaIngresoCamara()
    {
        return $this->fechaIngresoCamara;
    }

    /**
     * @param mixed $fechaIngresoCamara
     */
    public function setFechaIngresoCamara($fechaIngresoCamara): void
    {
        $this->fechaIngresoCamara = $fechaIngresoCamara;
    }

    /**
     * @return mixed
     */
    public function getFechaSalidaCamara()
    {
        return $this->fechaSalidaCamara;
    }

    /**
     * @param mixed $fechaSalidaCamara
     */
    public function setFechaSalidaCamara($fechaSalidaCamara): void
    {
        $this->fechaSalidaCamara = $fechaSalidaCamara;
    }

    /**
     * @return mixed
     */
    public function getFechaSalidaCamaraEstimada()
    {
        return $this->fechaSalidaCamaraEstimada;
    }

    /**
     * @param mixed $fechaSalidaCamaraEstimada
     */
    public function setFechaSalidaCamaraEstimada($fechaSalidaCamaraEstimada): void
    {
        $this->fechaSalidaCamaraEstimada = $fechaSalidaCamaraEstimada;
    }

    /**
     * @return mixed
     */
    public function getFechaSalidaCamaraReal()
    {
        return $this->fechaSalidaCamaraReal;
    }

    /**
     * @param mixed $fechaSalidaCamaraReal
     */
    public function setFechaSalidaCamaraReal($fechaSalidaCamaraReal): void
    {
        $this->fechaSalidaCamaraReal = $fechaSalidaCamaraReal;
    }

    public function getDiasEnCamara(){
        return '+' . $this->getTipoVariedad()->getTipoSubProducto()->getTipoProducto()->getCantDiasCamara() . ' day';
    }

    /**
     * @return mixed
     */
    public function getMesadas()
    {
        return $this->mesadas;
    }

    /**
     * @param mixed $mesadas
     */
    public function setMesadas($mesadas): void
    {
        $this->mesadas = $mesadas;
    }

    /**
     * @return mixed
     */
    public function addMesadas($mesada)
    {
        if (!$this->mesadas->contains($mesada)) {
            $this->mesadas[] = $mesada;
            $mesada->setPedidoProducto($this);
        }

        return $this;
    }

    public function getBandejas(){
        return ($this->cantBandejas.' (x'.$this->getTipoBandeja()->getNombre().') ');
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