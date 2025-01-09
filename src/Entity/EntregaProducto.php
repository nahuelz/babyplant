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
}
