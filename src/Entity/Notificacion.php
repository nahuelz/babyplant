<?php

namespace App\Entity;

use App\Entity\Traits\Auditoria;
use App\Entity\Traits\Rango;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Notificacion
 * 
 * @ORM\Table(name="notificacion")
 * @ORM\Entity
 * 
 * @Gedmo\SoftDeleteable(fieldName="fechaBaja")
 */
class Notificacion {

    use Auditoria;
    use Rango;

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
     * @ORM\Column(name="prioridad", type="string", nullable=false)
     */
    protected $prioridad;

    /**
     * @var string
     *
     * @ORM\Column(name="titulo", type="string", length=255, nullable=false)
     */
    protected $titulo;

    /**
     * @var string
     *
     * @ORM\Column(name="contenido", type="text", nullable=false)
     */
    protected $contenido;

    /**
     *
     * @var NotificacionUsuario
     * 
     * @ORM\OneToMany(targetEntity="NotificacionUsuario", mappedBy="notificacion", cascade={"all"})
     */
    protected $notificacionesUsuario;

    /**
     * @var string
     *
     * @ORM\Column(name="destinatarios", type="string", length=255, nullable=false)
     */
    protected $destinatarios;

    /**
     * Constructor
     */
    public function __construct() {
        $this->notificacionesUsuario = new ArrayCollection();
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
     * Set prioridad
     *
     * @param string $prioridad
     *
     * @return Notificacion
     */
    public function setPrioridad($prioridad) {
        $this->prioridad = $prioridad;

        return $this;
    }

    /**
     * Get prioridad
     *
     * @return string
     */
    public function getPrioridad() {
        return $this->prioridad;
    }

    /**
     * Get labelPrioridad
     *
     * @return string
     */
    public function getLabelPrioridad() {
        return $this->prioridad == 'A' ? 'Alta' : ($this->prioridad == 'M' ? 'Media' : 'Baja');
    }

    /**
     * Add notificacionesUsuario
     *
     * @param NotificacionUsuario $notificacionesUsuario
     *
     * @return Notificacion
     */
    public function addNotificacionesUsuario(NotificacionUsuario $notificacionesUsuario) {
        $this->notificacionesUsuario[] = $notificacionesUsuario;

        return $this;
    }

    /**
     * Remove notificacionesUsuario
     *
     * @param NotificacionUsuario $notificacionesUsuario
     */
    public function removeNotificacionesUsuario(NotificacionUsuario $notificacionesUsuario) {
        $this->notificacionesUsuario->removeElement($notificacionesUsuario);
    }

    /**
     * Get notificacionesUsuario
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotificacionesUsuario() {
        return $this->notificacionesUsuario;
    }

    /**
     * Set titulo
     *
     * @param string $titulo
     *
     * @return Notificacion
     */
    public function setTitulo($titulo) {
        $this->titulo = $titulo;

        return $this;
    }

    /**
     * Get titulo
     *
     * @return string
     */
    public function getTitulo() {
        return $this->titulo;
    }

    /**
     * Set contenido
     *
     * @param string $contenido
     *
     * @return Notificacion
     */
    public function setContenido($contenido) {
        $this->contenido = $contenido;

        return $this;
    }

    /**
     * Get contenido
     *
     * @return string
     */
    public function getContenido() {
        return $this->contenido;
    }

    /**
     * Set destinatarios
     *
     * @param string $destinatarios
     *
     * @return Notificacion
     */
    public function setDestinatarios($destinatarios) {
        $this->destinatarios = $destinatarios;

        return $this;
    }

    /**
     * Get destinatarios
     *
     * @return string
     */
    public function getDestinatarios() {
        return $this->destinatarios;
    }

}
