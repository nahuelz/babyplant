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
            ->select('
                u.id, 
                u.nombre, 
                u.apellido, 
                COUNT(p.id) AS cantidad,
                COALESCE(SUM(pp.cantidadBandejasPedidas), 0) AS total_bandejas
            ')
            ->join('p.cliente', 'u')
            ->leftJoin('p.pedidosProductos', 'pp')
            ->where('p.fechaCreacion BETWEEN :desde AND :hasta')
            ->andWhere('UPPER(u.nombre) NOT LIKE :stockFilter')
            ->andWhere('UPPER(u.apellido) NOT LIKE :stockFilter')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->setParameter('stockFilter', '%STOCK%')
            ->groupBy('u.id, u.nombre, u.apellido')
            ->orderBy('cantidad', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Cantidad de pedidos por cliente en un rango de fechas con paginación
     */
    public function getPedidosPorClientePaginado(\DateTime $desde, \DateTime $hasta, int $limite, int $offset): array
    {
        return $this->createQueryBuilder('p')
            ->select('
                u.id, 
                u.nombre, 
                u.apellido, 
                COUNT(p.id) AS cantidad,
                COALESCE(SUM(pp.cantidadBandejasPedidas), 0) AS total_bandejas
            ')
            ->join('p.cliente', 'u')
            ->leftJoin('p.pedidosProductos', 'pp')
            ->where('p.fechaCreacion BETWEEN :desde AND :hasta')
            ->andWhere('UPPER(u.nombre) NOT LIKE :stockFilter')
            ->andWhere('UPPER(u.apellido) NOT LIKE :stockFilter')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->setParameter('stockFilter', '%STOCK%')
            ->groupBy('u.id, u.nombre, u.apellido')
            ->orderBy('cantidad', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limite)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Obtener total de pedidos por cliente para paginación
     */
    public function getTotalPedidosPorCliente(\DateTime $desde, \DateTime $hasta): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT u.id)')
            ->join('p.cliente', 'u')
            ->leftJoin('p.pedidosProductos', 'pp')
            ->where('p.fechaCreacion BETWEEN :desde AND :hasta')
            ->andWhere('UPPER(u.nombre) NOT LIKE :stockFilter')
            ->andWhere('UPPER(u.apellido) NOT LIKE :stockFilter')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->setParameter('stockFilter', '%STOCK%')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
