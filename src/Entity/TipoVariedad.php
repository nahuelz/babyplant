<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoVariedad
 *
 * @ORM\Table(name="tipo_variedad")
 * @ORM\Entity
 */
class TipoVariedad extends EntidadBasica {

    /**
     * @ORM\ManyToOne(targetEntity=TipoSubProducto::class)
     * @ORM\JoinColumn(name="id_tipo_sub_producto", referencedColumnName="id")
     */
    private $tipoSubProducto;

    public function __construct() {
        $this->habilitado = true;
    }

    /**
     * @return mixed
     */
    public function getTipoSubProducto()
    {
        return $this->tipoSubProducto;
    }

    /**
     * @param mixed $tipoSubProducto
     */
    public function setTipoSubProducto($tipoSubProducto): void
    {
        $this->tipoSubProducto = $tipoSubProducto;
    }




}
