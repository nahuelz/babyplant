<?php

namespace App\Service;

use App\Entity\Adelanto;
use App\Entity\ConceptoLiquidacion;
use App\Entity\Constants\ConstanteEstadoLiquidacion;
use App\Entity\Constants\ConstanteTipoConceptoLiquidacion;
use App\Entity\EstadoLiquidacion;
use App\Entity\Liquidacion;
use App\Entity\TipoConceptoLiquidacion;
use App\Util\Decimal;
use Doctrine\ORM\EntityManagerInterface;

class LiquidacionService
{
    private EntityManagerInterface $em;
    private EstadoService $estadoService;

    public function __construct(EntityManagerInterface $em, EstadoService $estadoService)
    {
        $this->em = $em;
        $this->estadoService = $estadoService;
    }

    /**
     * Imputa todos los Adelanto pendientes del empleado (sin liquidación asignada)
     * a la liquidación indicada, generando/actualizando el ConceptoLiquidacion
     * de tipo ADELANTO correspondiente. Adelanto es la única fuente de verdad;
     * este concepto solo refleja la suma de los adelantos ya imputados.
     */
    public function imputarAdelantosPendientes(Liquidacion $liquidacion): void
    {
        if (!$liquidacion->isResumen()) {
            return;
        }

        $adelantosPendientes = $this->em->getRepository(Adelanto::class)
            ->createQueryBuilder('a')
            ->where('a.empleado = :empleado')
            ->andWhere('a.liquidacion IS NULL')
            ->andWhere('a.fecha >= :desde')
            ->andWhere('a.fecha <= :hasta')
            ->setParameters([
                'empleado' => $liquidacion->getEmpleado(),
                'desde' => $liquidacion->getFechaDesde(),
                'hasta' => $liquidacion->getFechaHasta(),
            ])
            ->getQuery()
            ->getResult();

        if (empty($adelantosPendientes)) {
            return;
        }

        $tipoAdelanto = $this->em->getRepository(TipoConceptoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteTipoConceptoLiquidacion::ADELANTO);

        $total = '0';
        foreach ($adelantosPendientes as $adelanto) {
            /* @var $adelanto Adelanto */
            $adelanto->setLiquidacion($liquidacion);
            $total = Decimal::add($total, (string) $adelanto->getImporte(), 2);
        }

        $concepto = $this->buscarConceptoExistente($liquidacion, $tipoAdelanto);
        if (!$concepto) {
            $concepto = new ConceptoLiquidacion();
            $concepto->setTipoConceptoLiquidacion($tipoAdelanto);
            $concepto->setDescripcion('Adelantos del período');
            $liquidacion->addConcepto($concepto);
        }

        $concepto->setCantidad(1);
        $concepto->setValorUnitario($total);
        $concepto->setImporte($total);

        $this->em->persist($concepto);

        $liquidacion->recalcularTotal();
    }

    private function buscarConceptoExistente(Liquidacion $liquidacion, TipoConceptoLiquidacion $tipo): ?ConceptoLiquidacion
    {
        foreach ($liquidacion->getConceptos() as $concepto) {
            if ($concepto->getTipoConceptoLiquidacion() === $tipo) {
                return $concepto;
            }
        }

        return null;
    }

    /**
     * Recalcula sueldo neto y total a pagar de la liquidación.
     */
    public function calcular(Liquidacion $liquidacion): void
    {
        $this->imputarAdelantosPendientes($liquidacion);
        $liquidacion->recalcularTotal();

        $padre = $liquidacion->getPadre();
        if ($padre !== null) {
            $padre->recalcularTotal();
        }

        $estadoCalculada = $this->em->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::CALCULADA);

        $this->estadoService->cambiarEstadoLiquidacion($liquidacion, $estadoCalculada, 'Liquidación calculada.');
    }

    /**
     * Aprueba una liquidación previamente calculada.
     */
    public function aprobar(Liquidacion $liquidacion): void
    {
        $estadoAprobada = $this->em->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::APROBADA);

        $this->estadoService->cambiarEstadoLiquidacion($liquidacion, $estadoAprobada, 'Liquidación aprobada.');
    }

    /**
     * Marca la liquidación como pagada. A partir de este estado no debería
     * permitirse editar sueldo, conceptos ni importes (validar en el controller/UI).
     */
    public function marcarComoPagada(Liquidacion $liquidacion): void
    {
        $estadoPagada = $this->em->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::PAGADA);

        $this->estadoService->cambiarEstadoLiquidacion($liquidacion, $estadoPagada, 'Liquidación pagada.');
    }

    /**
     * Revierte el pago de una liquidación PAGADA, anulando (soft-delete) sus
     * PagoEmpleado y devolviéndola a BORRADOR. Si es una liquidación mensual
     * (resumen), revierte en cascada todas sus semanas pagadas.
     */
    public function revertirPago(Liquidacion $liquidacion): void
    {
        $estadoBorrador = $this->em->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::BORRADOR);

        if ($liquidacion->getPadre() === null) {
            foreach ($liquidacion->getDetallesSemanales() as $detalle) {
                $this->anularPagos($detalle);
                $this->estadoService->cambiarEstadoLiquidacion(
                    $detalle,
                    $estadoBorrador,
                    'Pago revertido (liquidación mensual revertida).'
                );
            }
        }

        $this->anularPagos($liquidacion);
        $this->estadoService->cambiarEstadoLiquidacion($liquidacion, $estadoBorrador, 'Pago revertido.');
    }

    /**
     * Revierte la aprobación de una liquidación APROBADA devolviéndola a BORRADOR.
     * Si es una liquidación mensual (resumen), revierte también sus semanas aprobadas a BORRADOR.
     */
    public function revertirAprobacion(Liquidacion $liquidacion): void
    {
        $estadoBorrador = $this->em->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::BORRADOR);

        if ($liquidacion->getPadre() === null) {
            foreach ($liquidacion->getDetallesSemanales() as $detalle) {
                $codigoDetalle = $detalle->getEstado() ? $detalle->getEstado()->getCodigoInterno() : null;
                if ($codigoDetalle === ConstanteEstadoLiquidacion::APROBADA) {
                    $this->anularPagos($detalle);
                    $this->estadoService->cambiarEstadoLiquidacion(
                        $detalle,
                        $estadoBorrador,
                        'Aprobación revertida (liquidación mensual revertida).'
                    );
                }
            }
        }

        $this->anularPagos($liquidacion);
        $this->estadoService->cambiarEstadoLiquidacion($liquidacion, $estadoBorrador, 'Aprobación revertida.');
    }

    private function anularPagos(Liquidacion $liquidacion): void
    {
        foreach ($liquidacion->getPagos() as $pago) {
            $this->em->remove($pago);
        }
    }

    /**
     * Anula una liquidación y libera los adelantos imputados para que puedan
     * volver a imputarse en una nueva liquidación.
     */
    public function anular(Liquidacion $liquidacion, string $motivo): void
    {
        foreach ($liquidacion->getAdelantosImputados() as $adelanto) {
            $adelanto->setLiquidacion(null);
        }

        $estadoAnulada = $this->em->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::ANULADA);

        $this->estadoService->cambiarEstadoLiquidacion($liquidacion, $estadoAnulada, $motivo);
    }

    public function esEditable(Liquidacion $liquidacion): bool
    {
        $estado = $liquidacion->getEstado();
        if (!$estado) {
            return true;
        }

        return !in_array($estado->getCodigoInterno(), [ConstanteEstadoLiquidacion::PAGADA, ConstanteEstadoLiquidacion::ANULADA], true);
    }

}
