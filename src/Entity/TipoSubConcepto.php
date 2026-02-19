<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoSubProducto
 *
 * @ORM\Table(name="tipo_sub_concepto")
 * @ORM\Entity
 */
class TipoSubConcepto extends EntidadBasica
{

    /**
     * @ORM\ManyToOne(targetEntity=TipoConcepto::class)
     * @ORM\JoinColumn(name="id_tipo_concepto", referencedColumnName="id")
     */
    private $tipoConcepto;

    /**
     * @return mixed
     */
    public function getTipoConcepto()
    {
        return $this->tipoConcepto;
    }

    /**
     * @param mixed $tipoConcepto
     */
    public function setTipoConcepto($tipoConcepto): void
    {
        $this->tipoConcepto = $tipoConcepto;
    }


}