<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoBandeja
 *
 * @ORM\Table(name="tipo_bandeja")
 * @ORM\Entity
 */
class TipoBandeja extends EntidadBasica {

    /**
     * @var boolean
     *
     * @ORM\Column(name="estandar", type="boolean", options={"default": 1})
     */
    protected $estandar;
    public function __construct() {
        $this->habilitado = true;
    }

    /**
     * @return bool
     */
    public function isEstandar(): bool
    {
        return $this->estandar;
    }

    /**
     * @param bool $estandar
     */
    public function setEstandar(bool $estandar): void
    {
        $this->estandar = $estandar;
    }



}
