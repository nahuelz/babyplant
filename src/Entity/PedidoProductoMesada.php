<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Grupo
 *
 * @ORM\Table(name="pedido_producto_mesada")
 * @ORM\Entity()
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class PedidoProductoMesada {

    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=PedidoProducto::class, inversedBy="mesadas")
     * @ORM\JoinColumn(name="id_pedio_producto", referencedColumnName="id")
     */
    private $pedidoProducto;

    /**
     * @ORM\ManyToOne(targetEntity=Mesada::class, inversedBy="pedidosProductosMesadas", cascade={"all"})
     * @ORM\JoinColumn(name="id_mesada", referencedColumnName="id")
     */
    private $mesada;


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

    /**
     * @return mixed
     */
    public function getMesada()
    {
        return $this->mesada;
    }

    /**
     * @param mixed $mesada
     */
    public function setMesada($mesada): void
    {
        $this->mesada = $mesada;
    }



}