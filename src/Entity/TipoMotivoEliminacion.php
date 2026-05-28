<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Revision
 *
 * @ORM\Table(name="tipo_motivo_eliminacion")
 * @ORM\Entity
 */
class TipoMotivoEliminacion extends EntidadBasica {


    public function setNombre(string $nombre): self
    {
        parent::setNombre(mb_strtoupper($nombre, 'UTF-8'));
        return $this;
    }

}