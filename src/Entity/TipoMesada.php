<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TipoProducto
 *
 * @ORM\Table(name="tipo_mesada")
 * @ORM\Entity
 */
class TipoMesada extends EntidadBasica {

    /**
     * @ORM\Column(name="disponible", type="string", length=50, nullable=true)
     */
    private $disponible;

    /**
     * @ORM\Column(name="capacidad", type="string", length=50, nullable=true)
     */
    private $capacidad;

    /**
     * @ORM\Column(name="ocupado", type="string", length=50, nullable=true)
     */
    private $ocupado;

    /**
     * @ORM\ManyToOne(targetEntity=TipoProducto::class)
     * @ORM\JoinColumn(name="id_tipo_producto", referencedColumnName="id", nullable=false)
     */
    private $tipoProducto;

    public function __toString(): string
    {
        return 'N°'.$this->getNombre().' '.$this->getTipoProducto().' Disponible: '.$this->getDisponible();
    }

    /**
     * @return mixed
     */
    public function getCapacidad()
    {
        return $this->capacidad;
    }

    /**
     * @param mixed $capacidad
     */
    public function setCapacidad($capacidad): void
    {
        $this->capacidad = $capacidad;
    }

    /**
     * @return mixed
     */
    public function getOcupado()
    {
        return $this->ocupado;
    }

    /**
     * @param mixed $ocupado
     */
    public function setOcupado($ocupado): void
    {
        $this->ocupado = $ocupado;
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

    /**
     * @return mixed
     */
    public function getDisponible()
    {
        return $this->disponible;
    }

    /**
     * @param mixed $disponible
     */
    public function setDisponible($disponible): void
    {
        $this->disponible = $disponible;
    }



}