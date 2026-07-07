<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EstadoReventa
 *
 * @ORM\Table(name="estado_reventa")
 * @ORM\Entity()
 */
class EstadoReventa extends EntidadBasica {

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

    public function __toString(): string
    {
        return $this->getNombre();
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
    public function getColorIcono()
    {
        return $this->colorIcono;
    }

    /**
     * @param mixed $colorIcono
     */
    public function setColorIcono($colorIcono): void
    {
        $this->colorIcono = $colorIcono;
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
