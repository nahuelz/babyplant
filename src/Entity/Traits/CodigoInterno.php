<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * CodigoInterno
 */
trait CodigoInterno {

    /**
     * @var integer
     *
     * @ORM\Column(name="codigo_interno", type="integer", nullable=true)
     */
    protected $codigoInterno;

    /**
     * Set codigoInterno
     *
     * @param integer $codigoInterno
     */
    public function setCodigoInterno($codigoInterno) {
        $this->codigoInterno = $codigoInterno;

        return $this;
    }

    /**
     * Get codigoInterno
     *
     * @return integer 
     */
    public function getCodigoInterno() {
        return $this->codigoInterno;
    }

}
