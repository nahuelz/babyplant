<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * 
 */
class AuditoriaListener {

    /**
     * CLASE_USUARIO
     */
    const CLASE_USUARIO = 'App\Entity\Usuario';

    /**
     * TRAIT_AUDITORIA
     */
    const TRAIT_AUDITORIA = 'App\Entity\Traits\Auditoria';

    /**
     * TRAIT_HABILITADO
     */
    const TRAIT_HABILITADO = 'App\Entity\Traits\Habilitado';
    
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $_container;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
     */
    protected $_tokenStorage;

    /**
     * Constructor
     * 
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, TokenStorageInterface $tokenStorage) {

        $this->_container = $container;
        $this->_tokenStorage = $tokenStorage;
    }

    /**
     * Listener del evento "prePersist".
     * 
     * @param \Doctrine\Persistence\Event\LifecycleEventArgs $args
     * @return boolean
     */
    public function prePersist(LifecycleEventArgs $args) {
        $entidad = $args->getEntity();
        if ($this->esAuditable($entidad)) {
            $this->setUsuarioCreacion($entidad);
            $this->setUsuarioUltimaModificacion($entidad);
        }
    }

    /**
     * Listener del evento "preUpdate".
     * 
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs  $args
     * @return boolean
     */
    public function preUpdate(PreUpdateEventArgs $args) {
        $entidad = $args->getEntity();
        if ($this->esAuditable($entidad)) {
            $this->setUsuarioUltimaModificacion($entidad);
        }

        if ($this->esHabilitable($entidad) && !$entidad->getHabilitado()) {
            $entidad->setUsuarioDeshabilito($this->getUser());
        }
    }

    /**
     * Retorna si la entidad recibida como parámetro es o no auditable.
     * 
     * @param type $entidad
     * @return type bool
     */
    private function esAuditable($entidad) {

        $traits = $this->class_uses_deep($entidad);

        return (in_array(self::TRAIT_AUDITORIA, $traits)) //
                && !is_a($entidad, self::CLASE_USUARIO);
    }

    /**
     * Retorna si la entidad recibida como parámetro aplica el trait Habilitado.
     * 
     * @param type $entidad
     * @return type bool
     */
    private function esHabilitable($entidad) {
        $traits = $this->class_uses_deep($entidad);
        return in_array(self::TRAIT_HABILITADO, $traits);
    }

    /**
     * 
     * @param type $entidad
     */
    private function setUsuarioCreacion($entidad) {

        if (null != $this->getUser()) {
            $entidad->setUsuarioCreacion($this->getUser());
        }
    }

    /**
     * 
     * @param type $entidad
     */
    private function setUsuarioUltimaModificacion($entidad) {

        if (null != $this->getUser()) {
            $entidad->setUsuarioUltimaModificacion($this->getUser());
        }
    }

    /**
     * 
     * @return type
     */
    private function getUser() {

        $usuario = null;

        $token = $this->_tokenStorage->getToken();

        if (null != $token) {

            if (is_a($token->getUser(), self::CLASE_USUARIO)) {

                $usuario = $token->getUser();
            }
        }

        return $usuario;
    }

    /**
     * 
     * @param type $class
     * @param type $autoload
     * @return array
     */
    private function class_uses_deep($class, $autoload = true) {

        $traits = [];

        // Get traits of all parent classes
        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while ($class = get_parent_class($class));

        // Get traits of all parent traits
        $traitsToSearch = $traits;

        while (!empty($traitsToSearch)) {
            $newTraits = class_uses(array_pop($traitsToSearch), $autoload);
            $traits = array_merge($newTraits, $traits);
            $traitsToSearch = array_merge($newTraits, $traitsToSearch);
        }

        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }

        return array_unique($traits);
    }

}
