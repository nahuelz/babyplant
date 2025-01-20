<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * EntregaPedido
 *
 * @ORM\Table(name="entrega_producto")
 * @ORM\Entity()
 */
class EntregaProducto {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class, inversedBy="entregasProductos")
     * @ORM\JoinColumn(name="id_pedido_producto", referencedColumnName="id", nullable=false)
     */
    private mixed $pedidoProducto;

    /**
     * @ORM\ManyToOne(targetEntity=Remito::class, inversedBy="remitosProductos")
     * @ORM\JoinColumn(name="id_remito", referencedColumnName="id", nullable=false)
     */
    private mixed $remito;

    /**
     * @ORM\Column(name="cantidad_bandejas_entregadas", type="string", length=50, nullable=false)
     */
    private mixed $cantBandejasEntregadas;

    /**
     * @ORM\Column(name="cantidad_bandejas_pendientes", type="string", length=50, nullable=false)
     */
    private mixed $cantBandejasPendientes;

    /**
     * @ORM\ManyToOne(targetEntity=Mesada::class)
     * @ORM\JoinColumn(name="id_mesada_uno", referencedColumnName="id", nullable=false)
     */
    private $mesadaUno;

    /**
     * @ORM\ManyToOne(targetEntity=Mesada::class)
     * @ORM\JoinColumn(name="id_mesada_dos", referencedColumnName="id", nullable=true)
     */
    private $mesadaDos;

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

    public function getPedidoProducto(): mixed
    {
        return $this->pedidoProducto;
    }

    public function setPedidoProducto(mixed $pedidoProducto): void
    {
        $this->pedidoProducto = $pedidoProducto;
    }

    public function getRemito(): mixed
    {
        return $this->remito;
    }

    public function setRemito(mixed $remito): void
    {
        $this->remito = $remito;
    }

    public function getCantBandejasEntregadas(): mixed
    {
        return $this->cantBandejasEntregadas;
    }

    public function setCantBandejasEntregadas(mixed $cantBandejasEntregadas): void
    {
        $this->cantBandejasEntregadas = $cantBandejasEntregadas;
    }

    public function getCantBandejasPendientes(): mixed
    {
        return $this->cantBandejasPendientes;
    }

    public function setCantBandejasPendientes(mixed $cantBandejasPendientes): void
    {
        $this->cantBandejasPendientes = $cantBandejasPendientes;
    }

    /**
     * @return mixed
     */
    public function getMesadaUno()
    {
        return $this->mesadaUno;
    }

    /**
     * @param mixed $mesadaUno
     */
    public function setMesadaUno($mesadaUno): void
    {
        $this->mesadaUno = $mesadaUno;
    }

    /**
     * @return mixed
     */
    public function getMesadaDos()
    {
        return $this->mesadaDos;
    }

    /**
     * @param mixed $mesadaDos
     */
    public function setMesadaDos($mesadaDos): void
    {
        $this->mesadaDos = $mesadaDos;
    }

}
