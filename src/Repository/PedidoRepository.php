<?php

namespace App\Repository;

use App\Entity\Pedido;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class PedidoRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Pedido::class);
    }

    /**
     * Cantidad de pedidos por cliente en un rango de fechas
     */
    public function getPedidosPorCliente(\DateTime $desde, \DateTime $hasta): array
    {
        return $this->createQueryBuilder('p')
            ->select('u.id, u.nombre, u.apellido, COUNT(p.id) AS cantidad')
            ->join('p.cliente', 'u')
            ->where('p.fechaCreacion BETWEEN :desde AND :hasta')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->groupBy('u.id')
            ->orderBy('cantidad', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
