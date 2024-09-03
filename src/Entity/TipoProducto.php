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

    public function __construct() {
        $this->habilitado = true;
    }
}
