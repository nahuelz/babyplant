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

    /**
     * @ORM\Column(name="catidad_dias_camara", type="string", length=50, nullable=true)
     */
    private $cantDiasCamara;

    public function __construct() {
        $this->habilitado = true;
    }

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
    public function getCantDiasCamara()
    {
        return $this->cantDiasCamara;
    }

    /**
     * @param mixed $cantDiasCamara
     */
    public function setCantDiasCamara($cantDiasCamara): void
    {
        $this->cantDiasCamara = $cantDiasCamara;
    }




}
