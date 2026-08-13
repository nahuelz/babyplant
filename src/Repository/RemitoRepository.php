<?php

namespace App\Repository;

use App\Entity\Remito;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RemitoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Remito::class);
    }

    /**
     * Cantidad de remitos nuevos registrados en un rango de fechas.
     */
    public function contarRemitosNuevos(\DateTime $desde, \DateTime $hasta): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.cliente', 'u')
            ->where('r.fechaCreacion BETWEEN :desde AND :hasta')
            ->andWhere('r.fechaBaja IS NULL')
            ->andWhere('UPPER(u.nombre) NOT LIKE :stockFilter')
            ->andWhere('UPPER(u.apellido) NOT LIKE :stockFilter')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->setParameter('stockFilter', '%STOCK%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Listado paginado de remitos nuevos en un rango de fechas.
     * Cada fila representa un remito. Usado en el dashboard de estadisticas.
     */
    public function getRemitosNuevosDetalle(\DateTime $desde, \DateTime $hasta, int $limite = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->join('r.cliente', 'u')
            ->where('r.fechaCreacion BETWEEN :desde AND :hasta')
            ->andWhere('r.fechaBaja IS NULL')
            ->andWhere('UPPER(u.nombre) NOT LIKE :stockFilter')
            ->andWhere('UPPER(u.apellido) NOT LIKE :stockFilter')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->setParameter('stockFilter', '%STOCK%')
            ->orderBy('r.fechaCreacion', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    /**
     * Cantidad total de remitos nuevos en un rango de fechas (sin paginar).
     */
    public function contarRemitosNuevosDetalle(\DateTime $desde, \DateTime $hasta): int
    {
        return $this->contarRemitosNuevos($desde, $hasta);
    }
}
