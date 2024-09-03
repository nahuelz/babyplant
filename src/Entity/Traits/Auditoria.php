<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * Auditoria.
 */
trait Auditoria {

    /**
     * @var \App\Entity\Usuario
     * 
     * @ORM\ManyToOne(targetEntity="\App\Entity\Usuario")
     * @ORM\JoinColumn(name="id_usuario_creacion", referencedColumnName="id")
     * 
     */
    protected $usuarioCreacion;

    /**
     * @var \App\Entity\Usuario
     * 
     * @ORM\ManyToOne(targetEntity="\App\Entity\Usuario")
     * @ORM\JoinColumn(name="id_usuario_ultima_modificacion", referencedColumnName="id")
     * 
     */
    protected $usuarioUltimaModificacion;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="fecha_creacion", type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    protected $fechaCreacion;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="fecha_ultima_modificacion", type="datetime", nullable=false, options={"default":"CURRENT_TIMESTAMP"})
     */
    protected $fechaUltimaModificacion;

    /**
     * @var \DateTime $eliminado
     *
     * @ORM\Column(name="fecha_baja", type="datetime", nullable=true)
     */
    protected $fechaBaja;

    /**
     * 
     * @param \App\Entity\Usuario $usuario
     */
    public function setUsuarioCreacion($usuario = null) {
        $this->usuarioCreacion = $usuario;
    }

    /**
     * 
     * @return \App\Entity\Usuario
     */
    public function getUsuarioCreacion() {
        return $this->usuarioCreacion;
    }

    /**
     * 
     * @param \App\Entity\Usuario $usuario
     */
    public function setUsuarioUltimaModificacion($usuario = null) {
        $this->usuarioUltimaModificacion = $usuario;
    }

    /**
     * 
     * @return \App\Entity\Usuario
     */
    public function getUsuarioUltimaModificacion() {
        return $this->usuarioUltimaModificacion;
    }

    /**
     * Get fechaCreacion
     *
     * @return \DateTime 
     */
    public function getFechaCreacion() {
        return $this->fechaCreacion;
    }

    /**
     * Get fechaUltimaModificacion
     *
     * @return \DateTime 
     */
    public function getFechaUltimaModificacion() {
        return $this->fechaUltimaModificacion;
    }
    /**
     * Get fechaBaja
     *
     * @return \DateTime 
     */
    public function getFechaBaja() {
        return $this->fechaBaja;
    }

    /**
     * Set fechaBaja
     *
     * @param \DateTime $fechaBaja
     */
    public function setFechaBaja($fechaBaja) {
        $this->fechaBaja = $fechaBaja;

        return $this;
    }

}
