<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Usuario
 *
 */
trait Usuario {

    /**
     * @ORM\ManyToOne(targetEntity="\App\Entity\Usuario")
     * @ORM\JoinColumn(name="id_usuario", referencedColumnName="id", nullable=true)
     */
    protected $usuario;

    /**
     * Set usuario
     *
     * @param \App\Entity\Usuario $usuario
     */
    public function setUsuario(\App\Entity\Usuario $usuario = null) {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * Get usuario
     *
     * @return \App\Entity\Usuario
     */
    public function getUsuario() {
        return $this->usuario;
    }

}
