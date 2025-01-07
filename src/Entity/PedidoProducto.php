<?php /** @noinspection ALL */

namespace App\Entity;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Traits\Auditoria;
use App\Repository\PedidoProductoRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

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
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Pedido::class, inversedBy="pedidosProductos")
     * @ORM\JoinColumn(name="id_pedido", referencedColumnName="id", nullable=true)
     */
    private mixed $pedido;

    /**
     * @ORM\ManyToOne(targetEntity=TipoVariedad::class)
     * @ORM\JoinColumn(name="id_tipo_variedad", referencedColumnName="id", nullable=true)
     */
    private mixed $tipoVariedad;

    /**
     * @ORM\ManyToOne(targetEntity=TipoBandeja::class)
     * @ORM\JoinColumn(name="id_tipo_bandeja", referencedColumnName="id", nullable=true)
     */
    private mixed $tipoBandeja;

    /**
     * @ORM\Column(name="cantidad_bandejas_pedidas", type="integer", nullable=false)
     */
    private mixed $cantBandejasPedidas;

    /**
     * @ORM\Column(name="cantidad_bandejas_reales", type="integer", nullable=true)
     */
    private mixed $cantBandejasReales;

    /**
     * @ORM\Column(name="cantidad_bandejas_entregadas", type="integer", nullable=true)
     */
    private mixed $cantBandejasEntregadas;

    /**
     * @ORM\Column(name="cantidad_bandejas_faltantes", type="integer", nullable=true)
     */
    private mixed $cantBandejasFaltantes;

    /**
     * @ORM\Column(name="cantidad_semillas", type="integer", nullable=false)
     */
    private mixed $cantSemillas;

    /**
     * @ORM\Column(name="fecha_pedido", type="datetime", nullable=true)
     */
    private mixed $fechaPedido;

    /**
     * @ORM\Column(name="fecha_siembra_pedido", type="datetime", nullable=true)
     */
    private mixed $fechaSiembraPedido;

    /**
     * @ORM\Column(name="fecha_siembra_planificacion", type="datetime", nullable=true)
     */
    private mixed $fechaSiembraPlanificacion;

    /**
     * @ORM\Column(name="fecha_siembra_real", type="datetime", nullable=true)
     */
    private mixed $fechaSiembraReal;

    /**
     * @ORM\Column(name="fecha_entrada_camara", type="datetime", length=255, nullable=true)
     */
    private $fechaEntradaCamara;

    /**
     * @ORM\Column(name="fecha_entrada_camara_real", type="datetime", length=255, nullable=true)
     */
    private $fechaEntradaCamaraReal;

    /**
     * @ORM\Column(name="fecha_salida_camara", type="datetime", length=255, nullable=true)
     */
    private $fechaSalidaCamara;

    /**
     * @ORM\Column(name="fecha_salida_camara_real", type="datetime", length=255, nullable=true)
     */
    private $fechaSalidaCamaraReal;


    /**
     * @ORM\Column(name="fecha_entrega_pedido", type="datetime", nullable=true)
     */
    private DateTime $fechaEntregaPedido;

    /**
     * @ORM\Column(name="fecha_entrega_pedido_real", type="datetime", nullable=true)
     */
    private $fechaEntregaPedidoReal;


    /**
     * @ORM\Column(name="cantidad_dias_produccion", type="integer", nullable=false)
     */
    private mixed $cantDiasProduccion;

    /**
     * @ORM\ManyToOne(targetEntity=TipoOrigenSemilla::class)
     * @ORM\JoinColumn(name="id_tipo_origen_semilla", referencedColumnName="id", nullable=true)
     */
    private mixed $tipoOrigenSemilla;

    /**
     * @ORM\Column(name="otro_origen_semilla", type="string", length=50, nullable=true)
     */
    private mixed $otroOrigenSemilla;

    /**
     * @ORM\OneToMany(targetEntity=EstadoPedidoProductoHistorico::class, mappedBy="pedidoProducto", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoPedidoProducto::class)
     * @ORM\JoinColumn(name="id_estado_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $estado;

    /**
     * @ORM\Column(name="codigo_sobre", type="string", length=50, nullable=true)
     */
    private mixed $codigoSobre;

    /**
     * @ORM\Column(name="observacion", type="string", length=255, nullable=true)
     */
    private mixed $observacion;

    /**
     * @ORM\Column(name="hora_siembra", type="datetime", length=255, nullable=true)
     */
    private $horaSiembra;

    /**
     * @ORM\Column(name="numero_orden", type="integer", nullable=true)
     */
    private mixed $numeroOrden;

    /**
     * @ORM\OneToMany(targetEntity=PedidoProductoMesada::class, mappedBy="pedidoProducto", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private mixed $mesadas;

    /**
     * @ORM\OneToMany(targetEntity=RemitoProducto::class, mappedBy="pedidoProducto", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $remitosProductos;


    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
        $this->cantBandejasEntregadas = 0;
    }

    public function __toString()
    {
        return 'Pedido Producto N° '.$this->getId(). ' Orden Siembra: '.$this->getNumeroOrdenCompleto(). ' Producto: '.$this->getNombreCompleto(). ' Bandejas: '.$this->getCantBandejasReales().' (x'.$this->getTipoBandeja().')';
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
    public function getPedido(): mixed
    {
        return $this->pedido;
    }

    /**
     * @param mixed $pedido
     */
    public function setPedido(mixed $pedido): void
    {
        $this->pedido = $pedido;
    }

    /**
     * @return mixed
     */
    public function getTipoVariedad(): mixed
    {
        return $this->tipoVariedad;
    }

    /**
     * @param mixed $tipoVariedad
     */
    public function setTipoVariedad(mixed $tipoVariedad): void
    {
        $this->tipoVariedad = $tipoVariedad;
    }

    /**
     * @return mixed
     */
    public function getTipoBandeja(): mixed
    {
        return $this->tipoBandeja;
    }

    /**
     * @param mixed $tipoBandeja
     */
    public function setTipoBandeja(mixed $tipoBandeja): void
    {
        $this->tipoBandeja = $tipoBandeja;
    }

    /**
     * @return mixed
     */
    public function getCantBandejasPedidas(): mixed
    {
        return $this->cantBandejasPedidas;
    }

    /**
     * @param mixed $cantBandejasPedidas
     */
    public function setCantBandejasPedidas(mixed $cantBandejasPedidas): void
    {
        $this->cantBandejasPedidas = $cantBandejasPedidas;
        $this->cantBandejasFaltantes = $cantBandejasPedidas;
    }

    /**
     * @return mixed
     */
    public function getCantBandejasReales(): mixed
    {
        return $this->cantBandejasReales;
    }

    /**
     * @param mixed $cantBandejasReales
     */
    public function setCantBandejasReales(mixed $cantBandejasReales): void
    {
        $this->cantBandejasReales = $cantBandejasReales;
        $this->cantBandejasFaltantes = $cantBandejasReales;
    }

    /**
     * @return mixed
     */
    public function getCantSemillas(): mixed
    {
        return $this->cantSemillas;
    }

    /**
     * @param mixed $cantSemillas
     */
    public function setCantSemillas(mixed $cantSemillas): void
    {
        $this->cantSemillas = $cantSemillas;
    }

    /**
     * @return mixed
     */
    public function getFechaPedido(): mixed
    {
        return $this->fechaPedido;
    }

    /**
     * @param mixed $fechaPedido
     */
    public function setFechaPedido(mixed $fechaPedido): void
    {
        $this->fechaPedido = $fechaPedido;
    }

    /**
     * @return mixed
     */
    public function getFechaSiembraPedido(): mixed
    {
        return $this->fechaSiembraPedido;
    }

    /**
     * @param mixed $fechaSiembraPedido
     */
    public function setFechaSiembraPedido(mixed $fechaSiembraPedido): void
    {
        $this->fechaSiembraPedido = $fechaSiembraPedido;
        $this->setFechaSiembraPlanificacion($fechaSiembraPedido);
    }

    /**
     * @return mixed
     */
    public function getFechaSiembraReal(): mixed
    {
        return $this->fechaSiembraReal;
    }

    /**
     * @param mixed $fechaSiembraReal
     */
    public function setFechaSiembraReal(mixed $fechaSiembraReal): void
    {
        $this->fechaSiembraReal = $fechaSiembraReal;
    }


    public function getFechaEntradaCamara()
    {
        return $this->fechaEntradaCamara;
    }

    /**
     * @param mixed $fechaEntradaCamara
     */
    public function setFechaEntradaCamara(mixed $fechaEntradaCamara): void
    {
        $this->fechaEntradaCamara = $fechaEntradaCamara;
    }

    public function getFechaSalidaCamara()
    {
        return $this->fechaSalidaCamara;
    }

    /**
     * @param mixed $fechaSalidaCamara
     */
    public function setFechaSalidaCamara(mixed $fechaSalidaCamara): void
    {
        $this->fechaSalidaCamara = $fechaSalidaCamara;
    }

    public function getFechaSalidaCamaraReal()
    {
        return $this->fechaSalidaCamaraReal;
    }

    /**
     * @param mixed $fechaSalidaCamaraReal
     */
    public function setFechaSalidaCamaraReal(mixed $fechaSalidaCamaraReal): void
    {
        $this->fechaSalidaCamaraReal = $fechaSalidaCamaraReal;
    }

    /**
     * @return DateTime
     */
    public function getFechaEntregaPedido(): DateTime
    {
        return $this->fechaEntregaPedido;
    }

    /**
     * @param mixed $fechaEntregaPedido
     */
    public function setFechaEntregaPedido(mixed $fechaEntregaPedido): void
    {
        $this->fechaEntregaPedido = $fechaEntregaPedido;
    }

    /**
     * @return mixed
     */
    public function getFechaEntregaPedidoReal()
    {
        return $this->fechaEntregaPedidoReal;
    }

    /**
     * @param mixed $fechaEntregaPedidoReal
     */
    public function setFechaEntregaPedidoReal($fechaEntregaPedidoReal): void
    {
        $this->fechaEntregaPedidoReal = $fechaEntregaPedidoReal;
    }

    /**
     * @return mixed
     */
    public function getCantDiasProduccion(): mixed
    {
        return $this->cantDiasProduccion;
    }

    /**
     * @param mixed $cantDiasProduccion
     */
    public function setCantDiasProduccion(mixed $cantDiasProduccion): void
    {
        $this->cantDiasProduccion = $cantDiasProduccion;
    }

    /**
     * @return mixed
     */
    public function getTipoOrigenSemilla(): mixed
    {
        return $this->tipoOrigenSemilla;
    }

    /**
     * @param mixed $tipoOrigenSemilla
     */
    public function setTipoOrigenSemilla(mixed $tipoOrigenSemilla): void
    {
        $this->tipoOrigenSemilla = $tipoOrigenSemilla;
    }

    /**
     * @return mixed
     */
    public function getOtroOrigenSemilla(): mixed
    {
        return $this->otroOrigenSemilla;
    }

    /**
     * @param mixed $otroOrigenSemilla
     */
    public function setOtroOrigenSemilla(mixed $otroOrigenSemilla): void
    {
        $this->otroOrigenSemilla = $otroOrigenSemilla;
    }

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
    public function getEstado(): mixed
    {
        return $this->estado;
    }

    /**
     * @param mixed $estado
     */
    public function setEstado(mixed $estado): void
    {
        $this->estado = $estado;
    }

    /**
     * @return mixed
     */
    public function getCodigoSobre(): mixed
    {
        return $this->codigoSobre;
    }

    /**
     * @param mixed $codigoSobre
     */
    public function setCodigoSobre(mixed $codigoSobre): void
    {
        $this->codigoSobre = $codigoSobre;
    }

    /**
     * @return mixed
     */
    public function getObservacion(): mixed
    {
        return $this->observacion;
    }

    /**
     * @param mixed $observacion
     */
    public function setObservacion(mixed $observacion): void
    {
        $this->observacion = $observacion;
    }

    /**
     * @param mixed $numeroOrden
     */
    public function setNumeroOrden(mixed $numeroOrden): void
    {
        $this->numeroOrden = $numeroOrden;
    }

    public function getMesada()
    {
        $mesadas = '';
        foreach ($this->mesadas as $mesada){
            $mesadas .= $mesada->getMesada().', ';
        }
        return rtrim($mesadas, ", ");
    }

    /**
     * @return mixed
     */
    public function getMesadas(): mixed
    {
        return $this->mesadas;
    }

    /**
     * @param mixed $mesadas
     */
    public function setMesadas(mixed $mesadas): void
    {
        $this->mesadas = $mesadas;
    }

    public function getNombreCompleto(){
        return $this->getTipoVariedad()->getNombreCompleto();
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

    public function getHoraSiembra()
    {
        return $this->horaSiembra;
    }

    /**
     * @param mixed $horaSiembra
     */
    public function setHoraSiembra(mixed $horaSiembra): void
    {
        if ($horaSiembra != null) {
            $fecha = $this->getFechaSiembraReal();
            $hora = intval(substr($horaSiembra, 0, 2));
            $minutos = intval(substr($horaSiembra, 3, 2));
            $fecha->setTime($hora, $minutos);
            $this->horaSiembra = $fecha;
        }else{
            $this->horaSiembra = new DateTime();
        }
    }

    public function getTipoProducto(){
        return $this->getTipoVariedad()->getTipoSubProducto()->getTipoProducto();
    }

    public function getProductoBandeja(){
        return $this->getTipoVariedad()->getTipoSubProducto()->getTipoProducto() .' '. $this->getTipoVariedad().' (x'.$this->getTipoBandeja().')';
    }


    public function getNumeroOrdenCompleto()
    {
        return $this->numeroOrden ? $this->numeroOrden . ' '.strtoupper(substr($this->getTipoProducto(), 0, 3)) : '-';
    }

    /**
     * @return mixed
     */
    public function getNumeroOrden(): mixed
    {
        return $this->numeroOrden;
    }

    public function getDiasEnCamara(): string
    {
        return '+' . $this->getTipoVariedad()->getTipoSubProducto()->getTipoProducto()->getCantDiasCamara() . ' day';
    }

    public function addMesadas($mesada): void
    {
        if (!$this->mesadas->contains($mesada)) {
            $this->mesadas[] = $mesada;
            $mesada->setPedidoProducto($this);
        }
    }

    public function getBandejas(): string
    {
        return ($this->cantBandejasReales.' (x'.$this->getTipoBandeja()->getNombre().') ');
    }

    public function getCantidadDiasEnInvernaculo (): string
    {
        $fechaEntradaInvernaculo = $this->fechaSalidaCamaraReal;

        if ($this->getFechaEntregaPedidoReal() == null) {
            $hasta = new DateTime();
        }else{
            $hasta = $this->getFechaEntregaPedidoReal();
        }
        return($hasta->diff($fechaEntradaInvernaculo)->format("%d dias %h horas %i minutos"));
    }

    public function getCantidadDiasEnCamara (): string
    {
        $fechaSalidaCamara = $this->fechaSalidaCamaraReal != null ? $this->fechaSalidaCamaraReal : new DateTime();

         if ($this->fechaEntradaCamaraReal != null) {
            return ($this->fechaEntradaCamaraReal->diff($fechaSalidaCamara)->format("%d dias %h horas %i minutos")); ;
        } else {
            return '0';
        }
    }

    /**
     * @return mixed
     */
    public function getFechaSiembraPlanificacion(): mixed
    {
        return $this->fechaSiembraPlanificacion;
    }

    /**
     * @param mixed $fechaSiembraPlanificacion
     */
    public function setFechaSiembraPlanificacion(mixed $fechaSiembraPlanificacion): void
    {
        $this->fechaSiembraPlanificacion = $fechaSiembraPlanificacion;
    }

    /**
     * @return mixed
     */
    public function getFechaEntradaCamaraReal()
    {
        return $this->fechaEntradaCamaraReal;
    }

    /**
     * @param mixed $fechaEntradaCamaraReal
     */
    public function setFechaEntradaCamaraReal($fechaEntradaCamaraReal): void
    {
        $this->fechaEntradaCamaraReal = $fechaEntradaCamaraReal;
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
    }

    public function getCantBandejasEntregadas(): mixed
    {
        return $this->cantBandejasEntregadas;
    }

    public function setCantBandejasEntregadas(mixed $cantBandejasEntregadas): void
    {
        $this->cantBandejasEntregadas = $cantBandejasEntregadas;
    }

    public function getCantBandejasFaltantes(): mixed
    {
        return $this->cantBandejasFaltantes;
    }

    public function setCantBandejasFaltantes(mixed $cantBandejasFaltantes): void
    {
        $this->cantBandejasFaltantes = $cantBandejasFaltantes;
    }

    public function getPendiente(){
        $pendiente = 0;
        $descuento = 0;
        foreach ($this->getRemitosProductos() as $remitoProducto){
            $pendiente += $remitoProducto->getPrecioSubTotal();
            $descuento = $remitoProducto->getRemito()->getMontoDescuento();
        }
        $pendiente -= intval($descuento);
        return $pendiente;
    }
}