<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Grupo
 *
 * @ORM\Table(name="pedido_producto")
 * @ORM\Entity
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
     * @ORM\ManyToOne(targetEntity=Pedido::class)
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
     * @ORM\OneToMany(targetEntity=EstadoPedidoProductoHistorico::class, mappedBy="matriculado", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoPedidoProducto::class)
     * @ORM\JoinColumn(name="id_estado_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private $estado;

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








}