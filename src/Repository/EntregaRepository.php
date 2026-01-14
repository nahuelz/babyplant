<?php

namespace App\Repository;

use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Entrega;
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
}
