<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Habilitado
 *
 */
trait Habilitado {

    /**
     * @var boolean
     *
     * @ORM\Column(name="habilitado", type="boolean", options={"default": 1})
     */
    protected $habilitado;

    /**
     * @var \DateTime $fechaDeshabilitado
     *
     * @ORM\Column(name="fecha_deshabilitado", type="datetime", nullable=true)
     */
    protected $fechaDeshabilitado;

    /**
     * @var \App\Entity\Usuario
     * 
     * @ORM\ManyToOne(targetEntity="\App\Entity\Usuario")
     * @ORM\JoinColumn(name="id_usuario_deshabilito", referencedColumnName="id", nullable=true)
     */
    protected $usuarioDeshabilito;

    /**
     * Get habilitado
     *
     * @return boolean 
     */
    public function getHabilitado() {
        return $this->habilitado;
    }

    /**
     * Set habilitado
     *
     * @param boolean $habilitado
     */
    public function setHabilitado($habilitado) {
        $this->habilitado = $habilitado;

        if (!$habilitado) {
            $this->fechaDeshabilitado = new \DateTime();
        }

        return $this;
    }

    /**
     * Get fechaDeshabilitado
     *
     * @return \DateTime 
     */
    public function getFechaDeshabilitado() {
        return $this->fechaDeshabilitado;
    }

    /**
     * Set fechaDeshabilitado
     *
     * @param \DateTime $fechaDeshabilitado
     */
    public function setFechaDeshabilitado($fechaDeshabilitado) {
        $this->fechaDeshabilitado = $fechaDeshabilitado;
        return $this;
    }

    /**
     * Set usuarioDeshabilito
     * 
     * @param type $usuarioDeshabilito
     * @return $this
     */
    public function setUsuarioDeshabilito($usuarioDeshabilito = null) {
        $this->usuarioDeshabilito = $usuarioDeshabilito;

        return $this;
    }

    /**
     * Get usuarioDeshabilito
     */
    public function getUsuarioDeshabilito() {
        return $this->usuarioDeshabilito;
    }

}
