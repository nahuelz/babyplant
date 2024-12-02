<?php

namespace App\Repository;

use App\Entity\PedidoProducto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 *
 */
class PedidoProductoRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, PedidoProducto::class);
    }

    /**
     *
     * @return type
     */
    public function getSiguienteNumeroOrden($tipoProducto) {

        $query = $this->createQueryBuilder('p')
            ->select('p.numeroOrden')
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'p.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->andWhere('tp.id = :tipoProducto')
            ->setParameter('tipoProducto', $tipoProducto)
            ->orderBy('p.numeroOrden', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        try {
            $siguienteNumero = $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            $siguienteNumero = 0;
        }

        return $siguienteNumero + 1;
    }

    public function getPedidosAtrasados($idEstado) {

        $hoy = date('Ymd');
        $query = $this->createQueryBuilder('p')
            ->select('pe.id as idPedido, p.id, tp.nombre as nombreProducto, sb.nombre as nombreSubProducto, v.nombre as nombreVariedad, p.fechaSiembraPlanificacion')
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'p.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:Pedido', 'pe', Join::WITH, 'p.pedido = pe')
            ->andWhere('p.estado = :idEstado')
            ->andWhere('p.fechaSiembraPlanificacion < :hoy')
            ->setParameter('idEstado', $idEstado)
            ->setParameter('hoy', $hoy)
            ->getQuery();

        return $query->getResult();
    }
}
