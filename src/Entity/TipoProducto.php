<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoProducto
 *
 * @ORM\Table(name="tipo_producto")
 * @ORM\Entity
 */
class TipoProducto extends EntidadBasica {

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $color;

    public function __construct() {
        $this->habilitado = true;
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


}
