<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Concepto
 *
 * @ORM\Table(name="tipo_concepto")
 * @ORM\Entity
 */
class TipoConcepto extends EntidadBasica {


    public function setNombre(string $nombre): self
    {
        parent::setNombre(mb_strtoupper($nombre, 'UTF-8'));
        return $this;
    }

}