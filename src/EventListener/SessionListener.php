<?php

namespace App\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Usuario;
use Doctrine\DBAL\DBALException;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * 
 */
class SessionListener {

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * 
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManagerInterface $em
     * @param SessionInterface $session
     */
    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $em, SessionInterface $session) {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->session = $session;
    }

    /**
     * 
     * @param RequestEvent $event
     * @return void
     */
    public function onRequestListener(RequestEvent $event): void {

        // If its not te master request or token is null
        if (!$event->isMasterRequest() || $this->tokenStorage->getToken() === null) {
            return;
        }

        $user = $this->tokenStorage->getToken()->getUser();

        // Check if user is logged in
        if (!$user instanceof Usuario) {
            return;
        }

        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $stmt = $connection->prepare('UPDATE `sessions` SET `user_id` = :userId, `user_ip` = :userIp WHERE `sess_id` = :sessionId');
            $stmt->execute([
                'userId' => $user->getId(),
                'userIp' => $event->getRequest()->getClientIp(),
                'sessionId' => $this->session->getId(),
            ]);
            $connection->commit();
        } catch (DBALException $e) {
            $connection->rollBack();
        }
    }

}
