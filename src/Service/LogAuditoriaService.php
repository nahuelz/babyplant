<?php

namespace App\Service;

use App\Entity\API;
use App\Entity\LogAuditoria;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of SelectService
 */
class LogAuditoriaService
{

    private $em;

    public function __construct($doctrine) {
        $this->doctrine = $doctrine;
        $this->em = $doctrine->getManager();
    }

    /**
     *
     * @return type
     */
    public function generarLog($pedido, $accion, $modulo)
    {
        $log = new LogAuditoria();
        $log->setPedido($pedido);
        $log->setAccion($accion);
        $log->setModulo($modulo);
        $this->em->persist($log);
    }
}