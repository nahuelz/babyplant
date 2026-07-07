<?php

namespace App\Entity;

use App\Entity\Constants\ConstanteEstadoDevolucion;
use App\Entity\Constants\ConstanteEstadoReventa;
use App\Entity\Traits\Auditoria;
use App\Repository\DevolucionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="devolucion")
 * @ORM\Entity(repositoryClass=DevolucionRepository::class)
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Devolucion {

    use Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity=EntregaProducto::class, inversedBy="devoluciones")
     * @ORM\JoinColumn(name="id_entrega_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $entregaProducto = null;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="decimal", precision=6, scale=1, nullable=false)
     */
    private mixed $cantidadBandejas = null;

    /**
     * @ORM\Column(name="precio_unitario", type="decimal", precision=10, scale=2, nullable=true)
     */
    private mixed $precioUnitario = null;

    /**
     * @ORM\Column(name="fecha_devolucion", type="datetime", nullable=true)
     */
    private mixed $fechaDevolucion = null;

    /**
     * @ORM\Column(name="observacion", type="string", length=255, nullable=true)
     */
    private mixed $observacion = null;

    /**
     * @ORM\Column(name="vendias", type="boolean", nullable=true)
     */
    private mixed $vendias = null;

    /**
     * @ORM\Column(name="pagas", type="boolean", nullable=true)
     */
    private mixed $pagas = null;

    /**
     * @ORM\OneToMany(targetEntity=EstadoDevolucionHistorico::class, mappedBy="devolucion", cascade={"all"})
     * @ORM\OrderBy({"fecha" = "DESC", "id" = "DESC"})
     */
    private $historicoEstados;

    /**
     * @ORM\ManyToOne(targetEntity=EstadoDevolucion::class)
     * @ORM\JoinColumn(name="id_estado_devolucion", referencedColumnName="id", nullable=false)
     */
    private mixed $estado;

    /**
     * @ORM\OneToMany(targetEntity=Reventa::class, mappedBy="devolucion")
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $reventas;

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
        $this->reventas = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Devolución N° ' . $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEntregaProducto(): mixed
    {
        return $this->entregaProducto;
    }

    public function setEntregaProducto(mixed $entregaProducto): void
    {
        $this->entregaProducto = $entregaProducto;
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

    public function getFechaDevolucion(): mixed
    {
        return $this->fechaDevolucion;
    }

    public function setFechaDevolucion(mixed $fechaDevolucion): void
    {
        $this->fechaDevolucion = $fechaDevolucion;
    }

    public function getObservacion(): mixed
    {
        return $this->observacion;
    }

    public function setObservacion(mixed $observacion): void
    {
        $this->observacion = $observacion;
    }

    public function getVendias(): mixed
    {
        return $this->vendias;
    }

    public function setVendias(mixed $vendias): void
    {
        $this->vendias = $vendias;
    }

    public function getPagas(): mixed
    {
        return $this->pagas;
    }

    public function setPagas(mixed $pagas): void
    {
        $this->pagas = $pagas;
    }

    public function getCliente()
    {
        return $this->entregaProducto->getPedidoProducto()->getPedido()->getCliente();
    }

    public function getPedido()
    {
        return $this->entregaProducto->getPedidoProducto()->getPedido();
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

    public function addHistoricoEstado(EstadoDevolucionHistorico $historicoEstado): self {
        if (!$this->historicoEstados->contains($historicoEstado)) {
            $this->historicoEstados[] = $historicoEstado;
            $historicoEstado->setDevolucion($this);
        }

        return $this;
    }

    public function removeHistoricoEstado(EstadoDevolucionHistorico $historicoEstado): self {
        if ($this->historicoEstados->removeElement($historicoEstado)) {
            if ($historicoEstado->getDevolucion() === $this) {
                $historicoEstado->setDevolucion(null);
            }
        }

        return $this;
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

    public function getReventas()
    {
        return $this->reventas;
    }

    public function addReventa(Reventa $reventa): self {
        if (!$this->reventas->contains($reventa)) {
            $this->reventas[] = $reventa;
            $reventa->setDevolucion($this);
        }

        return $this;
    }

    public function removeReventa(Reventa $reventa): self {
        if ($this->reventas->removeElement($reventa)) {
            if ($reventa->getDevolucion() === $this) {
                $reventa->setDevolucion(null);
            }
        }

        return $this;
    }

    /**
     * Cantidad de bandejas revendidas (reventas no canceladas).
     * Valor siempre calculado, nunca almacenado.
     */
    public function getCantidadRevendida(): float|int
    {
        $cantidad = 0;
        foreach ($this->getReventas() as $reventa) {
            if ($reventa->getEstado() != null && $reventa->getEstado()->getCodigoInterno() == ConstanteEstadoReventa::CANCELADA) {
                continue;
            }
            $cantidad += $reventa->getCantidadBandejas();
        }
        return $cantidad == (int)$cantidad ? (int)$cantidad : (float)$cantidad;
    }

    /**
     * Cantidad de bandejas descartadas.
     * Solo aplica si la devolución fue descartada: lo que no se revendió se descarta.
     */
    public function getCantidadDescartada(): float|int
    {
        if ($this->getEstado() != null && $this->getEstado()->getCodigoInterno() == ConstanteEstadoDevolucion::DESCARTADA) {
            $cantidad = $this->cantidadBandejas - $this->getCantidadRevendida();
            return $cantidad == (int)$cantidad ? (int)$cantidad : (float)$cantidad;
        }
        return 0;
    }

    /**
     * Cantidad de bandejas disponibles para reventa.
     */
    public function getCantidadDisponible(): float|int
    {
        if ($this->getEstado() != null && $this->getEstado()->getCodigoInterno() == ConstanteEstadoDevolucion::DESCARTADA) {
            return 0;
        }
        $cantidad = $this->cantidadBandejas - $this->getCantidadRevendida();
        return $cantidad == (int)$cantidad ? (int)$cantidad : (float)$cantidad;
    }

    /**
     * Cantidad de bandejas pendientes de resolución (ni revendidas ni descartadas).
     */
    public function getCantidadPendiente(): float|int
    {
        return $this->getCantidadDisponible();
    }
}