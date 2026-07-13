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

        // Busca el numeroOrden del ÚLTIMO producto planificado de este tipo
        // (según el histórico más reciente), no el MAX. Esto permite que al
        // llegar a 1000 el contador se reinicie en 1 sin generar duplicados.
        $query = $this->createQueryBuilder('p')
            ->select('p.numeroOrden')
            ->join('p.historicoEstados', 'h')
            ->join('h.estado', 'e')
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'p.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->andWhere('tp.id = :tipoProducto')
            ->andWhere('e.codigoInterno = :planificado')
            ->andWhere('p.numeroOrden IS NOT NULL')
            ->setParameter('tipoProducto', $tipoProducto)
            ->setParameter('planificado', ConstanteEstadoPedidoProducto::PLANIFICADO)
            ->orderBy('h.fecha', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery();

        try {
            $ultimoNumero = $query->getSingleScalarResult();
        } catch (NoResultException $e) {
            $ultimoNumero = 0;
        }

        // Al llegar a 1000 se reinicia el contador en 1
        if ($ultimoNumero >= 1000) {
            return 1;
        }

        return $ultimoNumero + 1;
    }

    public function getProductosMasVendidos(\DateTimeInterface $fechaInicio, \DateTimeInterface $fechaFin, int $limite = 10): array
    {
        $queryBuilder = $this->createQueryBuilder('pp')
            ->select([
                "CONCAT(tp.nombre,' ',tsp.nombre,' ',tv.nombre) as producto",
                'SUM(ep.cantidadBandejas) as cantidad',
                'COUNT(DISTINCT ep.id) as total_ventas',
                'tp.color as color',
                'tv.id as tipo_variedad_id'
            ])
            ->join('pp.pedido', 'p')
            ->join('pp.tipoVariedad', 'tv')
            ->join('tv.tipoSubProducto', 'tsp')
            ->join('tsp.tipoProducto', 'tp')
            ->join('pp.estado', 'e')
            ->leftJoin('pp.entregasProductos', 'ep')
            ->where('ep.fechaCreacion BETWEEN :fechaInicio AND :fechaFin')
            ->andWhere('e.id IN (:estados)')
            ->andWhere('p.fechaBaja IS NULL AND pp.fechaBaja IS NULL') // Cambiado a IS NULL para registros activos
            ->setParameter('fechaInicio', $fechaInicio->format('Y-m-d 00:00:00'))
            ->setParameter('fechaFin', $fechaFin->format('Y-m-d 23:59:59'))
            ->setParameter('estados', [ConstanteEstadoPedidoProducto::ENTREGADO, ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL])
            ->groupBy('tv.id, tv.nombre')
            ->orderBy('cantidad', 'DESC')
            ->setMaxResults($limite);

        // Obtener la consulta SQL para depuración
        $query = $queryBuilder->getQuery();

        // Ejecutar y obtener resultados
        return $query->getResult();

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

    public function getProduccionPorProducto(\DateTime $desde, \DateTime $hasta): array
    {
        return $this->createQueryBuilder('pp')
            ->select([
                'tp.nombre AS producto',
                'SUM(pp.cantidadBandejasReales * tb.nombre) AS totalPlantas'
            ])
            ->join('pp.tipoVariedad', 'tv')
            ->join('tv.tipoSubProducto', 'tsp')
            ->join('tsp.tipoProducto', 'tp')
            ->join('pp.tipoBandeja', 'tb')
            ->where('pp.fechaSiembraReal BETWEEN :desde AND :hasta')
            ->andWhere('pp.fechaBaja IS NULL')
            ->groupBy('tp.id')
            ->orderBy('totalPlantas', 'DESC')
            ->setParameter('desde', (clone $desde)->setTime(0, 0, 0))
            ->setParameter('hasta', (clone $hasta)->setTime(23, 59, 59))
            ->getQuery()
            ->getArrayResult();
    }

}
