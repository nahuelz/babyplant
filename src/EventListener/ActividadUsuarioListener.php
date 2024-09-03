<?php

namespace App\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Doctrine\ORM\EntityManager;
use App\Entity\Usuario;
use DateTime;

/**
 * Listener que actualiza ultima actividad de usuario logueado
 */
class ActividadUsuarioListener {

    protected $tokenStorage;
    protected $entityManager;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManager $entityManager) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
    }

    /**
     *
     * @param KernelEvent $event
     * @return type
     */
    public function onCoreController(KernelEvent $event) {
        if (!$event->isMasterRequest()) {
            return;
        }

        // Check token authentication availability
        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (($user instanceof Usuario) && !($user->isActiveNow())) {
                $user->setLastSeen(new DateTime());
                $this->entityManager->flush();
            }
        }
    }

}
