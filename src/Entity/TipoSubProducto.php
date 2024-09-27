<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoSubProducto
 *
 * @ORM\Table(name="tipo_sub_producto")
 * @ORM\Entity
 */
class TipoSubProducto extends EntidadBasica {

    /**
     * @ORM\ManyToOne(targetEntity=TipoProducto::class)
     * @ORM\JoinColumn(name="id_tipo_producto", referencedColumnName="id")
     */
    private $tipoProducto;

    public function __construct() {
        $this->habilitado = true;
    }

    public function getNombreCompleto($nombreVariedad){
        return $this->getTipoProducto()->getNombre().' '.$this->getNombre(). ' '.$nombreVariedad;
    }

    /**
     * @return mixed
     */
    public function getTipoProducto()
    {
        return $this->tipoProducto;
    }

    /**
     * @param mixed $tipoProducto
     */
    public function setTipoProducto($tipoProducto): void
    {
        $this->tipoProducto = $tipoProducto;
    }


}
