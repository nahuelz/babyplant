<?php

namespace App\Service;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\PedidoProducto;
use App\Entity\TipoMotivoEliminacion;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class BandejaService
{
    private EntityManagerInterface $em;
    private EstadoService $estadoService;

    public function __construct(EntityManagerInterface $em, EstadoService $estadoService)
    {
        $this->em = $em;
        $this->estadoService = $estadoService;
    }

    /**
     * Eliminar bandejas de un pedido producto
     *
     * @param PedidoProducto $pedidoProducto
     * @param float $cantidadAEliminar
     * @param TipoMotivoEliminacion $motivoEliminacion
     * @return void
     */
    public function eliminarBandejas(PedidoProducto $pedidoProducto, float $cantidadAEliminar, TipoMotivoEliminacion $motivoEliminacion): void
    {
        // Actualizar la cantidad de bandejas eliminadas
        $pedidoProducto->setCantidadBandejasEliminadas($cantidadAEliminar);
        
        // Recalcular las bandejas disponibles
        $pedidoProducto->setCantidadBandejasDisponibles();
        $nuevaCantidadDisponible = $pedidoProducto->getCantidadBandejasDisponibles();

        // Si no quedan más bandejas disponibles, decidir estado final
        if ($nuevaCantidadDisponible == 0 && $pedidoProducto->getCantidadBandejasReservadasSinEntregar() == 0) {
            $codigoEstado = ConstanteEstadoPedidoProducto::ENTREGADO;

            // Si se eliminaron todas las bandejas reales del producto, pasa a ELIMINADO
            if ($pedidoProducto->getCantidadBandejasEliminadas() >= $pedidoProducto->getCantidadBandejasReales()) {
                $codigoEstado = ConstanteEstadoPedidoProducto::ELIMINADO;
            }

            $estado = $this->em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno($codigoEstado);
            $this->estadoService->cambiarEstadoPedidoProducto(
                $pedidoProducto,
                $estado,
                'Eliminación de bandejas: ' . $motivoEliminacion->getNombre()
            );
        }

        // Crear histórico con estado BANDEJA_ELIMINADA
        $estadoBandejaEliminada = $this->em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::BANDEJA_ELIMINADA);

        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoBandejaEliminada);
        $estadoPedidoProductoHistorico->setTipoMotivoEliminacion($motivoEliminacion);
        $estadoPedidoProductoHistorico->setMotivo('Eliminación de ' . $cantidadAEliminar . ' bandejas. Motivo: ' . $motivoEliminacion->getNombre());
        $estadoPedidoProductoHistorico->setCantidadBandejas($cantidadAEliminar);
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $this->em->persist($estadoPedidoProductoHistorico);
        $this->em->flush();
    }

    /**
     * Revertir bandejas eliminadas de un pedido producto
     *
     * @param PedidoProducto $pedidoProducto
     * @param float $cantidadARevertir
     * @return void
     */
    public function revertirBandejas(PedidoProducto $pedidoProducto, float $cantidadARevertir): void
    {
        // Validar que no supere las bandejas eliminadas
        if ($cantidadARevertir > $pedidoProducto->getCantidadBandejasEliminadas()) {
            throw new \InvalidArgumentException('No puede revertir más bandejas de las eliminadas');
        }

        // Restar de las bandejas eliminadas
        $nuevaCantidadEliminada = $pedidoProducto->getCantidadBandejasEliminadas() - $cantidadARevertir;
        $pedidoProducto->setCantidadBandejasEliminadasDirecta($nuevaCantidadEliminada);
        
        // Recalcular las bandejas disponibles
        $pedidoProducto->setCantidadBandejasDisponibles();
        $nuevaCantidadDisponible = $pedidoProducto->getCantidadBandejasDisponibles();

        // Si vuelve a tener bandejas disponibles, regresar a EN_INVERNACULO
        // solo cuando el estado actual era ENTREGADO o ELIMINADO
        $estadoActual = $pedidoProducto->getEstado()?->getCodigoInterno();
        if (
            $nuevaCantidadDisponible > 0
            && in_array($estadoActual, [ConstanteEstadoPedidoProducto::ELIMINADO, ConstanteEstadoPedidoProducto::ENTREGADO], true)
        ) {
            $estadoEnInvernaculo = $this->em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::EN_INVERNACULO);
            $this->estadoService->cambiarEstadoPedidoProducto(
                $pedidoProducto,
                $estadoEnInvernaculo,
                'Reversión de bandejas eliminadas'
            );
        }

        // Crear histórico con estado REVERTIR_BANDEJAS
        $estadoRevertirBandejas = $this->em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::REVERTIR_BANDEJAS);

        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoRevertirBandejas);
        $estadoPedidoProductoHistorico->setMotivo('Reversión de ' . $cantidadARevertir . ' bandejas eliminadas');
        $estadoPedidoProductoHistorico->setCantidadBandejas($cantidadARevertir);
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $this->em->persist($estadoPedidoProductoHistorico);
        $this->em->flush();
    }
}