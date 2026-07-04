<?php

namespace App\Entity;

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
     * @ORM\ManyToOne(targetEntity=EntregaProducto::class)
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

    public function __construct()
    {
        $this->historicoEstados = new ArrayCollection();
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
}