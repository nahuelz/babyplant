<?php

namespace App\Entity;

use App\Repository\TipoMesadaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * TipoProducto
 *
 * @ORM\Table(name="tipo_mesada")
 * @ORM\Entity(repositoryClass=TipoMesadaRepository::class)
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
     * @ORM\Column(name="numero", type="integer", nullable=false)
     */
    private int $numero;

    /**
     * @ORM\ManyToOne(targetEntity=TipoProducto::class)
     * @ORM\JoinColumn(name="id_tipo_producto", referencedColumnName="id", nullable=true)
     */
    private $tipoProducto;

    /**
     * @ORM\OneToMany(targetEntity=Mesada::class, mappedBy="tipoMesada", cascade={"all"})
     */
    private $mesadas;

    public function __construct() {
        parent::__construct();
        $this->ocupado = 0;
        $this->capacidad = 500;
        $this->habilitado = true;
        $this->mesadas = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'N°'.$this->getNumero().' '.$this->getTipoProducto().' Ocupado: '.$this->getOcupado();
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
        $ocupado = 0;
        foreach ($this->mesadas as $mesada) {
            $ocupado +=$mesada->getCantidadBandejas();
        }
        return $ocupado;
    }

    /**
     * @param int $ocupado
     */
    public function setOcupado(int $ocupado): void
    {
        $this->ocupado = $ocupado;
    }

    public function getTipoProducto()
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

    /**
     * @return mixed
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * @param mixed $numero
     */
    public function setNumero(int $numero): void
    {
        $this->numero = $numero;
        $this->setNombre($numero);
    }

    /**
     * @return mixed
     */
    public function getMesadas()
    {
        return $this->mesadas;
    }

    /**
     * @param mixed $mesadas
     */
    public function setMesadas($mesadas): void
    {
        $this->mesadas = $mesadas;
    }

    public function actualizarOcupado(): void
    {
        $this->ocupado = $this->getOcupado();
    }


}