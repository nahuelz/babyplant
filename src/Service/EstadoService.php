<?php

namespace App\Service;

use App\Entity\Devolucion;
use App\Entity\Entrega;
use App\Entity\EntregaProducto;
use App\Entity\EstadoDevolucion;
use App\Entity\EstadoDevolucionHistorico;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaHistorico;
use App\Entity\EstadoEntregaProducto;
use App\Entity\EstadoEntregaProductoHistorico;
use App\Entity\EstadoGasto;
use App\Entity\EstadoGastoHistorico;
use App\Entity\EstadoMesada;
use App\Entity\EstadoMesadaHistorico;
use App\Entity\EstadoRemito;
use App\Entity\EstadoRemitoHistorico;
use App\Entity\EstadoReservaHistorico;
use App\Entity\EstadoReventa;
use App\Entity\EstadoReventaHistorico;
use App\Entity\Gasto;
use App\Entity\Mesada;
use App\Entity\Remito;
use App\Entity\Reserva;
use App\Entity\Reventa;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\PedidoProducto;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use DateTime;

class EstadoService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Cambiar estado de PedidoProducto
     */
    public function cambiarEstadoPedidoProducto(PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto, string $motivo): void
    {
        $pedidoProducto->setEstado($estadoProducto);
        $historico = new EstadoPedidoProductoHistorico();
        $historico->setPedidoProducto($pedidoProducto);
        $this->crearHistorico($historico, $pedidoProducto, $estadoProducto, $motivo);
    }

    /**
     * Cambiar estado de Entrega
     */
    public function cambiarEstadoEntrega(Entrega $entrega, EstadoEntrega $estadoEntrega, string $motivo): void
    {
        $entrega->setEstado($estadoEntrega);
        $historico = new EstadoEntregaHistorico();
        $historico->setEntrega($entrega);
        $this->crearHistorico($historico, $entrega, $estadoEntrega, $motivo);
    }

    /**
     * Cambiar estado de EntregaProducto
     */
    public function cambiarEstadoEntregaProducto(EntregaProducto $entregaProducto, EstadoEntregaProducto $estadoEntregaProducto, string $motivo): void
    {
        $entregaProducto->setEstado($estadoEntregaProducto);
        $historico = new EstadoEntregaProductoHistorico();
        $historico->setEntregaProducto($entregaProducto);
        $this->crearHistorico($historico, $entregaProducto, $estadoEntregaProducto, $motivo);
    }

    /**
     * Cambiar estado de Remito
     */
    public function cambiarEstadoRemito(Remito $remito, EstadoRemito $estadoRemito, string $motivo): void
    {
        $remito->setEstado($estadoRemito);
        $historico = new EstadoRemitoHistorico();
        $historico->setRemito($remito);
        $this->crearHistorico($historico, $remito, $estadoRemito, $motivo);
    }

    /**
     * Cambiar estado de Mesada
     */
    public function cambiarEstadoMesada(Mesada $mesada, EstadoMesada $estadoMesada, string $motivo): void
    {
        $mesada->setEstado($estadoMesada);
        $historico = new EstadoMesadaHistorico();
        $historico->setMesada($mesada);
        $historico->setCantidadBandejas($mesada->getCantidadBandejas()); // Caso especial
        $this->crearHistorico($historico, $mesada, $estadoMesada, $motivo);
    }

    private function crearHistorico(object $historico, object $entidad, object $estado, string $motivo): void
    {
        $historico->setFecha(new DateTime());
        $historico->setEstado($estado);
        $historico->setMotivo($motivo);
        $entidad->addHistoricoEstado($historico);
        $this->em->persist($historico);
    }

    public function cambiarEstadoReserva(Reserva $reserva, $estadoReserva, string $motivo): void
    {
        $reserva->setEstado($estadoReserva);
        $estadoReservaHistorico = new EstadoReservaHistorico();
        $estadoReservaHistorico->setReserva($reserva);
        $this->crearHistorico($estadoReservaHistorico, $reserva, $estadoReserva, $motivo);
    }

    /**
     * Cambiar estado de Reventa
     */
    public function cambiarEstadoReventa(Reventa $reventa, EstadoReventa $estadoReventa, string $motivo): void
    {
        $reventa->setEstado($estadoReventa);
        $historico = new EstadoReventaHistorico();
        $historico->setReventa($reventa);
        $this->crearHistorico($historico, $reventa, $estadoReventa, $motivo);
    }

    /**
     * Cambiar estado de Devolucion
     */
    public function cambiarEstadoDevolucion(Devolucion $devolucion, EstadoDevolucion $estadoDevolucion, string $motivo): void
    {
        $devolucion->setEstado($estadoDevolucion);
        $historico = new EstadoDevolucionHistorico();
        $historico->setDevolucion($devolucion);
        $this->crearHistorico($historico, $devolucion, $estadoDevolucion, $motivo);
    }

    /**
     * Cambiar estado de Gasto
     */
    public function cambiarEstadoGasto(Gasto $gasto, EstadoGasto $estadoGasto, string $motivo): void
    {
        $gasto->setEstadoGasto($estadoGasto);
        $historico = new EstadoGastoHistorico();
        $historico->setGasto($gasto);
        $this->crearHistorico($historico, $gasto, $estadoGasto, $motivo);
    }

}
