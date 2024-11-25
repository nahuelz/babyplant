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
     * @ORM\Column(name="capacidad", type="integer", length=50, nullable=true)
     */
    private int $capacidad;

    /**
     * @ORM\Column(name="ocupado", type="integer", length=50, nullable=true, options={"default": 0})
     */
    private int $ocupado;

    /**
     * @ORM\ManyToOne(targetEntity=TipoProducto::class)
     * @ORM\JoinColumn(name="id_tipo_producto", referencedColumnName="id", nullable=true)
     */
    private TipoProducto $tipoProducto;

    public function __construct() {
        parent::__construct();
        $this->ocupado = 0;
    }

    public function __toString(): string
    {
        return 'N°'.$this->getNombre().' '.$this->getTipoProducto().' Disponible: '.$this->getDisponible();
    }

    /**
     * @return int
     */
    public function getCapacidad(): int
    {
        return $this->capacidad;
    }

    /**
     * @param int $capacidad
     */
    public function setCapacidad(int $capacidad): void
    {
        $this->capacidad = $capacidad;
    }

    /**
     * @return int
     */
    public function getOcupado(): int
    {
        return $this->ocupado;
    }

    /**
     * @param int $ocupado
     */
    public function setOcupado(int $ocupado): void
    {
        $this->ocupado = $ocupado;
    }

    /**
     * @return TipoProducto
     */
    public function getTipoProducto(): TipoProducto
    {
        return $this->tipoProducto;
    }

    /**
     * @param TipoProducto $tipoProducto
     */
    public function setTipoProducto(TipoProducto $tipoProducto): void
    {
        $this->tipoProducto = $tipoProducto;
        $tipoProducto->setUltimaMesada($this);
    }

    /**
     * @return mixed
     */
    public function getDisponible(): int
    {
        return ($this->capacidad - $this->ocupado);
    }

    public function sumarOcupado($cantidad): void{
        $this->ocupado += $cantidad;
    }



}