<?php

namespace App\Service;

use App\Entity\Entrega;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaHistorico;
use App\Entity\EstadoMesada;
use App\Entity\EstadoMesadaHistorico;
use App\Entity\EstadoRemito;
use App\Entity\EstadoRemitoHistorico;
use App\Entity\Mesada;
use App\Entity\Remito;
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
}
