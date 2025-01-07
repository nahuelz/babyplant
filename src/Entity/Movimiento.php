<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity()
 */
class Movimiento {
    use Auditoria;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $descripcion;

    /**
     * @ORM\ManyToOne(targetEntity=CuentaCorriente::class, inversedBy="movimientos")
     * @ORM\JoinColumn(name="id_cuenta_corriente", referencedColumnName="id")
     */
    private $cuentaCorriente;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCuentaCorriente()
    {
        return $this->cuentaCorriente;
    }

    /**
     * @param mixed $cuentaCorriente
     */
    public function setCuentaCorriente($cuentaCorriente): void
    {
        $this->cuentaCorriente = $cuentaCorriente;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }


}