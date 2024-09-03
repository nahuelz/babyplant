<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoBandeja
 *
 * @ORM\Table(name="tipo_origen_semilla")
 * @ORM\Entity
 */
class TipoOrigenSemilla extends EntidadBasica {

    public function __construct() {
        $this->habilitado = true;
    }

}
