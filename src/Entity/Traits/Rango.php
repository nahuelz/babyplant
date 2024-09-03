<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * Rango 
 * 
 * Especifica fechaDesde y fechaHasta. 
 *
 */
trait Rango {

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fecha_desde", type="datetime", nullable=false)
     */
    protected $fechaDesde;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fecha_hasta", type="datetime", nullable=true)
     */
    protected $fechaHasta;

    /**
     * Set fechaDesde
     *
     * @param \DateTime $fechaDesde
     */
    public function setFechaDesde($fechaDesde) {
        $this->fechaDesde = $fechaDesde;

        return $this;
    }

    /**
     * Get fechaDesde
     *
     * @return \DateTime
     */
    public function getFechaDesde() {
        return $this->fechaDesde;
    }

    /**
     * Set fechaHasta
     *
     * @param \DateTime $fechaHasta
     */
    public function setFechaHasta($fechaHasta) {
        $this->fechaHasta = $fechaHasta;

        return $this;
    }

    /**
     * Get fechaHasta
     *
     * @return \DateTime
     */
    public function getFechaHasta() {
        return $this->fechaHasta;
    }

}
