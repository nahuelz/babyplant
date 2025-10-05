<?php

namespace App\Repository;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\PedidoProducto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraints\Type;

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

    public function getProductosMasVendidos(\DateTimeInterface $fechaInicio, \DateTimeInterface $fechaFin, int $limite = 10): array
    {
        return $this->createQueryBuilder('pp')
            ->select([
                'tv.nombre as producto',
                'SUM(ep.cantidadBandejas) as cantidad',
                'COUNT(DISTINCT p.id) as total_ventas',
                'tv.id as tipo_variedad_id'
            ])
            ->join('pp.pedido', 'p')
            ->join('pp.tipoVariedad', 'tv')
            ->join('pp.estado', 'e')
            ->leftJoin('pp.entregasProductos', 'ep')
            ->where('pp.fechaEntregaPedidoReal BETWEEN :fechaInicio AND :fechaFin')
            ->andWhere('e.id IN (:estados)')
            ->andWhere('p.fechaBaja IS NULL' AND 'pp.fechaBaja IS NULL')
            ->setParameter('fechaInicio', $fechaInicio->format('Y-m-d 00:00:00'))
            ->setParameter('fechaFin', $fechaFin->format('Y-m-d 23:59:59'))
            ->setParameter('estados', [ConstanteEstadoPedidoProducto::ENTREGADO, ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL])
            ->groupBy('tv.id, tv.nombre')
            ->orderBy('cantidad', 'DESC')
            ->setMaxResults($limite)
            ->getQuery()
            ->getResult();
    }

    public function getPedidosAtrasados($idEstado) {

        $hoy = date('Ymd');
        $query = $this->createQueryBuilder('p')
            ->select('pe.id as idPedido, p.id, tp.nombre as nombreProducto, sb.nombre as nombreSubProducto, v.nombre as nombreVariedad, p.fechaSiembraPlanificacion, p.fechaSiembraReal, p.numeroOrden')
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

    /*
    public function getProductosEnMesada($id)
    {
        $query = $this->createQueryBuilder('pp')
            ->select("pp.id, tp.nombre as nombreProducto,pp.numeroOrden, concat(tp.nombre,' ', sb.nombre,' ',v.nombre) as producto, ppm.cantidadBandejas")
            ->leftJoin('App:Pedido', 'p', Join::WITH, 'pp.pedido = p')
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:PedidoProductoMesada', 'ppm', Join::WITH, 'ppm.pedidoProducto = pp')
            ->andWhere('ppm.tipoMesada = :id')
            ->setParameter('id', $id)
            ->getQuery();

        return $query->getResult();
    }
    */
}
