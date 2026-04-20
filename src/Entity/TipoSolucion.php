<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Solucion
 *
 * @ORM\Table(name="tipo_solucion")
 * @ORM\Entity
 */
class TipoSolucion extends EntidadBasica {


    public function setNombre(string $nombre): self
    {
        parent::setNombre(mb_strtoupper($nombre, 'UTF-8'));
        return $this;
    }

}