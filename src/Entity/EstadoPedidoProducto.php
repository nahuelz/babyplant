<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EstadoPedidoProducto
 *
 * @ORM\Table(name="estado_pedido_producto")
 * @ORM\Entity()
 */
class EstadoPedidoProducto extends EntidadBasica
{
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $inicial;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $icono;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $colorIcono;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $className;

    public function getInicial(): ?string
    {
        return $this->inicial;
    }

    public function setInicial(?string $inicial): self
    {
        $this->inicial = $inicial;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color): void
    {
        $this->color = $color;
    }

    /**
     * @return mixed
     */
    public function getIcono()
    {
        return $this->icono;
    }

    /**
     * @param mixed $icono
     */
    public function setIcono($icono): void
    {
        $this->icono = $icono;
    }

    /**
     * @return mixed
     */
    public function setColorIcono($colorIcono)
    {
        $this->colorIcono = $colorIcono;
    }

    /**
     * @return mixed
     */
    public function getColorIcono()
    {
        return $this->colorIcono;
    }

    /**
     * @return mixed
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param mixed $className
     */
    public function setClassName($className): void
    {
        $this->className = $className;
    }





}
