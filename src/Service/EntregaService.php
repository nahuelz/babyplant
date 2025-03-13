<?php

namespace App\Service;

use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteEstadoMesada;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\DatosEntrega;
use App\Entity\Entrega;
use App\Entity\EntregaProducto;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaHistorico;
use App\Entity\EstadoMesada;
use App\Entity\EstadoMesadaHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\Mesada;
use App\Entity\PedidoProducto;
use DateTime;
use Doctrine\Persistence\ObjectManager;

class EntregaService {

    function entregar(ObjectManager $em, Entrega $entrega){
        /** @var EntregaProducto $entregaProducto */
        foreach ($entrega->getEntregasProductos() as $entregaProducto){

            $bandejasAEntregar = $entregaProducto->getCantidadBandejas();

            /* @var $pedidoProducto PedidoProducto */
            $pedidoProducto = $entregaProducto->getPedidoProducto();

            $cantidadBandejasEntregadas = $pedidoProducto->getCantidadBandejasEntregadas();
            $cantidadBandejasSinEntregar = $pedidoProducto->getCantidadBandejasSinEntregar();

            // SI ENTREGO TODAS LAS BANDEJAS DEL PEDIDO EL ESTADO PASA A ENTREGADO COMPLETO SI NO A ENTREGADO PARCIAL
            if ($cantidadBandejasSinEntregar == 0){
                $estadoPedidoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::ENTREGADO);
                $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::ENTREGADO);
                $pedidoProducto->setFechaEntregaPedidoReal(new DateTime());
            }else{
                $estadoPedidoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL);
                $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::ENTREGADO_PARCIAL);
            }

            $datosEntrega = new DatosEntrega();
            $datosEntrega->setPedidoProducto($pedidoProducto);
            $datosEntrega->setEntrega($entrega);
            $datosEntrega->setCantidadBandejasEntregadas($cantidadBandejasEntregadas);
            $datosEntrega->setCantidadBandejasSinEntregar($cantidadBandejasSinEntregar);
            $datosEntrega->setCantidadBandejasAEntregar($bandejasAEntregar);
            $datosEntrega->setMesadaUno($pedidoProducto->getMesadaUno());
            $datosEntrega->setMesadaDos($pedidoProducto->getMesadaDos());
            $this->entregarBandejas($em, $pedidoProducto, $estadoMesada, $bandejasAEntregar);
            $em->persist($datosEntrega);

            $this->cambiarEstadoPedido($em, $pedidoProducto, $estadoPedidoProducto, $datosEntrega);

            $pedidoProducto->setCantidadBandejasDisponibles();

        }
        $estadoEntrega = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::SIN_REMITO);
        $this->cambiarEstadoEntrega($em, $entrega, $estadoEntrega);

        $em->flush();

        return true;
    }

    public function entregarBandejas($em, $pedidoProducto, $estadoMesada, $bandejasAEntregar): void
    {
        $mesadaUno = $pedidoProducto->getMesadaUno();
        $mesadaDos = $pedidoProducto->getMesadaDos();
        $bandejasEnMesadaUno = $mesadaUno != null ? $mesadaUno->getCantidadBandejas() : null;
        $bandejasEnMesadaDos = $mesadaDos != null ? $mesadaDos->getCantidadBandejas() : null;

        if($bandejasEnMesadaUno >= $bandejasAEntregar){
            $mesadaUno->entregarBandejas($bandejasAEntregar);
            $this->cambiarEstadoMesada($em, $mesadaUno, $estadoMesada);
        }else{
            $badejasRestantes = $bandejasAEntregar - $bandejasEnMesadaUno;
            $mesadaUno->entregarBandejas($bandejasEnMesadaUno);
            $this->cambiarEstadoMesada($em, $mesadaUno, $estadoMesada);
            // SI QUEDAN MÁS BANDEJAS POR ENTREGAR QUE LAS QUE HAY EN LA MESADA HUBO ERROR, SE DESCUENTAN SOLO LAS QUE HAY
            if ($badejasRestantes > $bandejasEnMesadaDos){
                $badejasRestantes = $bandejasEnMesadaDos;
            }
            $mesadaDos->entregarBandejas($badejasRestantes);
            $this->cambiarEstadoMesada($em, $mesadaDos, $estadoMesada);
        }
    }

    /**
     *
     * @param ObjectManager $em
     * @param Mesada $mesada
     * @param EstadoMesada $estadoMesada
     */
    private function cambiarEstadoMesada(ObjectManager $em, Mesada $mesada, EstadoMesada $estadoMesada): void
    {
        $mesada->setEstado($estadoMesada);
        $estadoMesadaHistorico = new EstadoMesadaHistorico();
        $estadoMesadaHistorico->setMesada($mesada);
        $estadoMesadaHistorico->setFecha(new DateTime());
        $estadoMesadaHistorico->setEstado($estadoMesada);
        $estadoMesadaHistorico->setCantidadBandejas($mesada->getCantidadBandejas());
        $estadoMesadaHistorico->setMotivo('Entrega de producto.');
        $mesada->addHistoricoEstado($estadoMesadaHistorico);

        $em->persist($estadoMesadaHistorico);
    }

    /**
     *
     * @param ObjectManager $em
     * @param PedidoProducto $pedidoProducto
     * @param EstadoPedidoProducto $estadoProducto
     * @param null $datosEntrega
     */
    private function cambiarEstadoPedido(ObjectManager $em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto, $datosEntrega = null): void
    {
        $pedidoProducto->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico->setMotivo('Entrega de bandejas');
        $estadoPedidoProductoHistorico->setDatosEntrega($datosEntrega);
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $em->persist($estadoPedidoProductoHistorico);
    }

    /**
     *
     * @param ObjectManager $em
     * @param Entrega $entrega
     * @param EstadoEntrega $estadoEntrega
     */
    private function cambiarEstadoEntrega(ObjectManager $em, Entrega $entrega, EstadoEntrega $estadoEntrega): void
    {
        $entrega->setEstado($estadoEntrega);
        $estadoEntregaHistorico = new EstadoEntregaHistorico();
        $estadoEntregaHistorico->setEntrega($entrega);
        $estadoEntregaHistorico->setFecha(new DateTime());
        $estadoEntregaHistorico->setEstado($estadoEntrega);
        $estadoEntregaHistorico->setMotivo('Entrega de producto');
        $entrega->addHistoricoEstado($estadoEntregaHistorico);

        $em->persist($estadoEntregaHistorico);
    }
}
