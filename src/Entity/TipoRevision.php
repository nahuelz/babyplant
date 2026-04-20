<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Revision
 *
 * @ORM\Table(name="tipo_revision")
 * @ORM\Entity
 */
class TipoRevision extends EntidadBasica {


    public function setNombre(string $nombre): self
    {
        parent::setNombre(mb_strtoupper($nombre, 'UTF-8'));
        return $this;
    }

}