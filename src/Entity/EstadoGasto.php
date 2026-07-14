<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EstadoGasto
 *
 * @ORM\Table(name="estado_gasto")
 * @ORM\Entity()
 */
class EstadoGasto extends EntidadBasica {

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $colorIcono;

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


}
