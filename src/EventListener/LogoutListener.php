<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutListener implements LogoutHandlerInterface {

    private $entityManager;
    private $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security) {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @{inheritDoc}
     */
    public function logout(Request $request, Response $response, TokenInterface $token) {
        $user = $token->getUser();
    }

}
