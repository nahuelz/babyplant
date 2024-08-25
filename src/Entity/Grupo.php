<?php

namespace App\Entity;

use App\Entity\Traits;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Grupo
 *
 * @ORM\Table(name="grupo")
 * @ORM\Entity
 *
 * @UniqueEntity(fields="nombre", message="El nombre del grupo ya está en uso.")
 *
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Grupo {

    use Traits\CodigoInterno,
        Traits\Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nombre", type="string", length=512, nullable=false)
     */
    protected $nombre;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string
     *
     * @ORM\Column(name="descripcion", type="string", length=512, nullable=true)
     */
    protected $descripcion;

    /**
     * @ORM\ManyToMany(targetEntity="Usuario", mappedBy="grupos")
     *
     */
    protected $usuarios;

    /**
     * Constructor
     */
    public function __construct() {
        $this->usuarios = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * To String
     *
     * @return string
     */
    public function __toString() {
        return $this->getNombre();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set nombre
     *
     * @param string $nombre
     * @return Grupo
     */
    public function setNombre($nombre) {
        $this->nombre = $nombre;

        return $this;
    }

    /**
     * Get nombre
     *
     * @return string
     */
    public function getNombre() {
        return $this->nombre;
    }

    /**
     * Set descripcion
     *
     * @param string $descripcion
     * @return Grupo
     */
    public function setDescripcion($descripcion) {
        $this->descripcion = $descripcion;

        return $this;
    }

    /**
     * Get descripcion
     *
     * @return string
     */
    public function getDescripcion() {
        return $this->descripcion;
    }

    /**
     * Add usuarios
     *
     * @param Usuario $usuarios
     * @return Grupo
     */
    public function addUsuario(\App\Entity\Usuario $usuarios) {
        $this->usuarios[] = $usuarios;

        return $this;
    }

    /**
     * Remove usuarios
     *
     * @param Usuario $usuarios
     */
    public function removeUsuario(\App\Entity\Usuario $usuarios) {
        $this->usuarios->removeElement($usuarios);
    }

    /**
     * Get usuarios
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsuarios() {
        return $this->usuarios;
    }

    /**
     * {@inheritdoc}
     */
    public function addRole($role) {
        if (!$this->hasRole($role)) {
            $this->roles[] = strtoupper($role);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole($role) {
        return in_array(strtoupper($role), $this->roles, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRole($role) {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }

        return $this;
    }

}
