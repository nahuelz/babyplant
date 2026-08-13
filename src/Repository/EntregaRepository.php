<?php

namespace App\Repository;

use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Entrega;
use App\Entity\EntregaProducto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntregaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entrega::class);
    }

    public function findEntregasSinRemitoPorCliente(int $idCliente): array
    {
        return $this->createQueryBuilder('e')
            ->select("e.id, concat('Entrega N° ', e.id) as denominacion")
            ->where('e.clienteEntrega = :cliente')
            ->andWhere('e.estado IN (:estado)')
            ->setParameter('cliente', $idCliente)
            ->setParameter('estado', [
                ConstanteEstadoEntrega::SIN_REMITO,
                ConstanteEstadoEntrega::ENTREGADO_SIN_REMITO
            ])
            ->orderBy('e.id', 'ASC')
            ->groupBy('e.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cantidad de entregas nuevas registradas en un rango de fechas
     */
    public function contarEntregasNuevas(\DateTime $desde, \DateTime $hasta): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->join('e.clienteEntrega', 'u')
            ->where('e.fechaCreacion BETWEEN :desde AND :hasta')
            ->andWhere('e.fechaBaja IS NULL')
            ->andWhere('UPPER(u.nombre) NOT LIKE :stockFilter')
            ->andWhere('UPPER(u.apellido) NOT LIKE :stockFilter')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->setParameter('stockFilter', '%STOCK%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Detalle de productos entregados en un rango de fechas (según fecha de creación de la Entrega),
     * con paginación. Usado en el dashboard de estadísticas para mostrar el detalle de "entregas nuevas".
     */
    public function getEntregasNuevasDetalle(\DateTime $desde, \DateTime $hasta, int $limite = 10, int $offset = 0): array
    {
        return $this->queryEntregasNuevasDetalle($desde, $hasta)
            ->setFirstResult($offset)
            ->setMaxResults($limite)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Cantidad total de filas de detalle de entregas nuevas en un rango de fechas (sin paginar)
     */
    public function contarEntregasNuevasDetalle(\DateTime $desde, \DateTime $hasta): int
    {
        return (int) $this->queryEntregasNuevasDetalle($desde, $hasta)
            ->select('COUNT(ep.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function queryEntregasNuevasDetalle(\DateTime $desde, \DateTime $hasta)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select([
                "CONCAT(tp.nombre,' ',tsp.nombre,' ',tv.nombre) as producto",
                'tp.color as colorProducto',
                'ep.cantidadBandejas as cantidadBandejas',
                'tb.nombre as tipoBandeja',
                "CONCAT(u.nombre,', ',u.apellido) as cliente",
                'u.id as idCliente',
                'e.id as idEntrega',
                'e.fechaCreacion as fechaCreacion',
            ])
            ->from(EntregaProducto::class, 'ep')
            ->join('ep.entrega', 'e')
            ->join('e.clienteEntrega', 'u')
            ->join('ep.pedidoProducto', 'pp')
            ->join('pp.tipoVariedad', 'tv')
            ->join('tv.tipoSubProducto', 'tsp')
            ->join('tsp.tipoProducto', 'tp')
            ->join('pp.tipoBandeja', 'tb')
            ->where('e.fechaCreacion BETWEEN :desde AND :hasta')
            ->andWhere('e.fechaBaja IS NULL AND ep.fechaBaja IS NULL')
            ->andWhere('UPPER(u.nombre) NOT LIKE :stockFilter')
            ->andWhere('UPPER(u.apellido) NOT LIKE :stockFilter')
            ->setParameter('desde', $desde->format('Y-m-d 00:00:00'))
            ->setParameter('hasta', $hasta->format('Y-m-d 23:59:59'))
            ->setParameter('stockFilter', '%STOCK%')
            ->orderBy('e.fechaCreacion', 'DESC');
    }
}
