<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * NotificacionUsuario
 * 
 * @ORM\Table(name="notificacion_usuario")
 * @ORM\Entity
 * 
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class NotificacionUsuario {

    use Traits\Auditoria;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Notificacion", inversedBy="notificacionesUsuario")
     * @ORM\JoinColumn(name="id_notificacion", referencedColumnName="id", nullable=false)
     */
    protected $notificacion;

    /**
     * @ORM\ManyToOne(targetEntity="Usuario")
     * @ORM\JoinColumn(name="id_usuario", referencedColumnName="id", nullable=false)
     */
    protected $usuario;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set notificacion
     *
     * @param Notificacion $notificacion
     *
     * @return NotificacionUsuario
     */
    public function setNotificacion(Notificacion $notificacion) {
        $this->notificacion = $notificacion;

        return $this;
    }

    /**
     * Get notificacion
     *
     * @return Notificacion
     */
    public function getNotificacion() {
        return $this->notificacion;
    }

    /**
     * Set usuario
     *
     * @param Usuario $usuario
     *
     * @return NotificacionUsuario
     */
    public function setUsuario(Usuario $usuario) {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * Get usuario
     *
     * @return Usuario
     */
    public function getUsuario() {
        return $this->usuario;
    }

}
