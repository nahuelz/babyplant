<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="proveedor")
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Proveedor
{
    use Auditoria;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Nombre comercial o razón social
     *
     * @ORM\Column(name="nombre", type="string", length=255)
     */
    private $nombre;

    /**
     * CUIT/CUIL
     *
     * @ORM\Column(name="cuit", type="string", length=20, nullable=true, unique=true)
     */
    private $cuit;

    /**
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(name="telefono", type="string", length=100, nullable=true)
     */
    private $telefono;

    /**
     * @ORM\Column(name="direccion", type="string", length=255, nullable=true)
     */
    private $direccion;

    /**
     * Opcional:
     * Responsable Inscripto / Monotributo / Consumidor Final / Exento
     *
     * @ORM\Column(name="condicion_iva", type="string", length=100, nullable=true)
     */
    private $condicionIva;

    /**
     * Observaciones internas
     *
     * @ORM\Column(name="observaciones", type="text", nullable=true)
     */
    private $observaciones;

    /**
     * @var boolean
     *
     * @ORM\Column(name="habilitado", type="boolean", options={"default": 1})
     */
    protected $habilitado;

    public function __construct()
    {
        $this->habilitado = true;
    }

    public function __toString(): string
    {
        return $this->nombre ?? 'Proveedor';
    }

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
    public function getNombre()
    {
        return $this->nombre;
    }

    /**
     * @param mixed $nombre
     */
    public function setNombre($nombre): void
    {
        $this->nombre = $nombre;
    }

    /**
     * @return mixed
     */
    public function getCuit()
    {
        return $this->cuit;
    }

    /**
     * @param mixed $cuit
     */
    public function setCuit($cuit): void
    {
        $this->cuit = $cuit;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getTelefono()
    {
        return $this->telefono;
    }

    /**
     * @param mixed $telefono
     */
    public function setTelefono($telefono): void
    {
        $this->telefono = $telefono;
    }

    /**
     * @return mixed
     */
    public function getDireccion()
    {
        return $this->direccion;
    }

    /**
     * @param mixed $direccion
     */
    public function setDireccion($direccion): void
    {
        $this->direccion = $direccion;
    }

    /**
     * @return mixed
     */
    public function getCondicionIva()
    {
        return $this->condicionIva;
    }

    /**
     * @param mixed $condicionIva
     */
    public function setCondicionIva($condicionIva): void
    {
        $this->condicionIva = $condicionIva;
    }

    /**
     * @return mixed
     */
    public function getObservaciones()
    {
        return $this->observaciones;
    }

    /**
     * @param mixed $observaciones
     */
    public function setObservaciones($observaciones): void
    {
        $this->observaciones = $observaciones;
    }

    public function getHabilitado(): bool
    {
        return $this->habilitado;
    }

    public function setHabilitado(bool $habilitado): void
    {
        $this->habilitado = $habilitado;
    }
}