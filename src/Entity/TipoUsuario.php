<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoProducto
 *
 * @ORM\Table(name="tipo_usuario")
 * @ORM\Entity
 */
class TipoUsuario extends EntidadBasica {

    public function __construct() {
        $this->habilitado = true;
    }
}
