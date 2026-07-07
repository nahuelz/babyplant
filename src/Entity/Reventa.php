<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Reventa
 *
 * Dueña del negocio de reventa de bandejas devueltas.
 * La EntregaProducto asociada es solamente la materialización logística.
 *
 * @ORM\Table(name="reventa")
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Reventa {

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
     * @ORM\ManyToOne(targetEntity=Devolucion::class, inversedBy="reventas")
     * @ORM\JoinColumn(name="id_devolucion", referencedColumnName="id", nullable=false)
     */
    private $devolucion;

    /**
     * @ORM\ManyToOne(targetEntity=Usuario::class)
     * @ORM\JoinColumn(name="id_cliente", referencedColumnName="id", nullable=false)
     */
    private $cliente;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="decimal", precision=6, scale=1, nullable=false)
     */
    private $cantidadBandejas;

    /**
     * @ORM\Column(name="precio_unitario", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $precioUnitario;

    /**
     * Precio unitario de la devolución al momento de crear la reventa.
     *
     * @ORM\Column(name="precio_unitario_original", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $precioUnitarioOriginal;

    /**
     * Materialización logística de la reventa.
     *
     * @ORM\OneToOne(targetEntity=EntregaProducto::class)
     * @ORM\JoinColumn(name="id_entrega_producto", referencedColumnName="id", nullable=true)
     */
    private $entregaProducto;

    /**
     * @ORM\Column(name="fecha_reventa", type="datetime", nullable=true)
     */
    private $fechaReventa;

    /**
     * @ORM\Column(name="observacion", type="string", length=255, nullable=true)
     */
    private $observacion;

    /**
     * @ORM\OneToMany(targetEntity=EstadoReventaHistorico::class, mappedBy="reventa", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoReventa::class)
     * @ORM\JoinColumn(name="id_estado_reventa", referencedColumnName="id", nullable=false)
     */
    private mixed $estado;

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Reventa N° ' . $this->getId();
    }

    public function getCodigo(){
        return str_pad($this->id, 6, "0", STR_PAD_LEFT);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getDevolucion(): mixed
    {
        return $this->devolucion;
    }

    public function setDevolucion(mixed $devolucion): void
    {
        $this->devolucion = $devolucion;
    }

    public function getCliente(): mixed
    {
        return $this->cliente;
    }

    public function setCliente(mixed $cliente): void
    {
        $this->cliente = $cliente;
    }

    public function getCantidadBandejas(): mixed
    {
        return $this->cantidadBandejas;
    }

    public function setCantidadBandejas(mixed $cantidadBandejas): void
    {
        $this->cantidadBandejas = $cantidadBandejas;
    }

    public function getPrecioUnitario(): mixed
    {
        return $this->precioUnitario;
    }

    public function setPrecioUnitario(mixed $precioUnitario): void
    {
        $this->precioUnitario = $precioUnitario;
    }

    public function getPrecioUnitarioOriginal(): mixed
    {
        return $this->precioUnitarioOriginal;
    }

    public function setPrecioUnitarioOriginal(mixed $precioUnitarioOriginal): void
    {
        $this->precioUnitarioOriginal = $precioUnitarioOriginal;
    }

    public function getEntregaProducto(): mixed
    {
        return $this->entregaProducto;
    }

    public function setEntregaProducto(mixed $entregaProducto): void
    {
        $this->entregaProducto = $entregaProducto;
    }

    public function getFechaReventa(): mixed
    {
        return $this->fechaReventa;
    }

    public function setFechaReventa(mixed $fechaReventa): void
    {
        $this->fechaReventa = $fechaReventa;
    }

    public function getObservacion(): mixed
    {
        return $this->observacion;
    }

    public function setObservacion(mixed $observacion): void
    {
        $this->observacion = $observacion;
    }

    public function getHistoricoEstados()
    {
        return $this->historicoEstados;
    }

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

    public function addHistoricoEstado(EstadoReventaHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setReventa($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoReventaHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            if ($historicoEstado->getReventa() === $this) {
                $historicoEstado->setReventa(null);
            }
        }

        return $this;
    }

    /**
     * Cliente que devolvió originalmente el producto.
     */
    public function getClienteOriginal()
    {
        return $this->devolucion?->getCliente();
    }

    /**
     * Producto de producción origen (trazabilidad).
     */
    public function getPedidoProducto()
    {
        return $this->devolucion?->getEntregaProducto()?->getPedidoProducto();
    }

    /**
     * Entrega generada al materializar la reventa.
     */
    public function getEntrega()
    {
        return $this->entregaProducto?->getEntrega();
    }

    /**
     * Calcula el valor total basado en precio unitario y cantidad
     */
    public function getValorTotal(): mixed
    {
        if ($this->precioUnitario && $this->cantidadBandejas) {
            return $this->precioUnitario * $this->cantidadBandejas;
        }
        return null;
    }
}
