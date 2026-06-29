<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Repository\DevolucionRepository;
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
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class)
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $pedidoProducto = null;

    /**
     * @ORM\Column(name="cantidad_bandejas", type="decimal", precision=6, scale=1, nullable=false)
     */
    private mixed $cantidadBandejas = null;

    /**
     * @ORM\Column(name="fecha_devolucion", type="datetime", nullable=true)
     */
    private mixed $fechaDevolucion = null;

    /**
     * @ORM\Column(name="observacion", type="string", length=255, nullable=true)
     */
    private mixed $observacion = null;

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

    public function getPedidoProducto(): mixed
    {
        return $this->pedidoProducto;
    }

    public function setPedidoProducto(mixed $pedidoProducto): void
    {
        $this->pedidoProducto = $pedidoProducto;
    }

    public function getCantidadBandejas(): mixed
    {
        return $this->cantidadBandejas;
    }

    public function setCantidadBandejas(mixed $cantidadBandejas): void
    {
        $this->cantidadBandejas = $cantidadBandejas;
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

    public function getCliente()
    {
        return $this->pedidoProducto->getPedido()->getCliente();
    }

    public function getPedido()
    {
        return $this->pedidoProducto->getPedido();
    }
}
