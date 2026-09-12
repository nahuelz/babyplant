<?php

namespace App\Service;

use App\Entity\Constants\ConstanteEstadoDevolucion;
use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteEstadoEntregaProducto;
use App\Entity\Constants\ConstanteEstadoReventa;
use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\Constants\ConstanteModoPago;
use App\Entity\Constants\ConstanteTipoMovimiento;
use App\Entity\Devolucion;
use App\Entity\Entrega;
use App\Entity\EntregaProducto;
use App\Entity\EstadoDevolucion;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaProducto;
use App\Entity\EstadoReventa;
use App\Entity\Reventa;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ReventaService
{
    private EntityManagerInterface $em;
    private EstadoService $estadoService;
    private MovimientoService $movimientoService;

    public function __construct(EntityManagerInterface $em, EstadoService $estadoService, MovimientoService $movimientoService)
    {
        $this->em = $em;
        $this->estadoService = $estadoService;
        $this->movimientoService = $movimientoService;
    }

    /**
     * Crea una reventa sobre una devolución.
     * La reventa nace PENDIENTE_ENTREGA; la entrega se materializa después con la acción "Entregar".
     */
    public function crear(Reventa $reventa): void
    {
        $devolucion = $reventa->getDevolucion();

        if ($devolucion == null) {
            throw new \DomainException('Debe seleccionar una devolución.');
        }

        if ($reventa->getCantidadBandejas() == null || $reventa->getCantidadBandejas() <= 0) {
            throw new \DomainException('La cantidad de bandejas debe ser mayor a 0.');
        }

        if ($reventa->getCantidadBandejas() > $devolucion->getCantidadDisponible()) {
            throw new \DomainException('La cantidad a revender no puede superar las bandejas disponibles de la devolución (' . $devolucion->getCantidadDisponible() . ').');
        }

        if ($devolucion->getEstado() != null && $devolucion->getEstado()->getCodigoInterno() == ConstanteEstadoDevolucion::DESCARTADA) {
            throw new \DomainException('La devolución fue descartada, no se puede revender.');
        }

        // CONGELAR EL PRECIO ORIGINAL DE LA DEVOLUCION
        $reventa->setPrecioUnitarioOriginal($devolucion->getPrecioUnitario());

        if ($reventa->getFechaReventa() == null) {
            $reventa->setFechaReventa(new DateTime());
        }

        $devolucion->addReventa($reventa);

        $estadoReventa = $this->em->getRepository(EstadoReventa::class)->findOneBy(['codigoInterno' => ConstanteEstadoReventa::PENDIENTE_ENTREGA]);
        $this->estadoService->cambiarEstadoReventa($reventa, $estadoReventa, 'Creación de reventa.');

        $this->actualizarEstadoDevolucion($devolucion, 'Reventa de ' . $reventa->getCantidadBandejas() . ' bandejas.');

        $this->em->persist($reventa);
        $this->em->flush();
    }

    /**
     * Materializa la entrega de la reventa: crea la Entrega y la EntregaProducto (esReventa)
     * para el cliente de la reventa. No descuenta mesadas ni bandejas de producción.
     * La entrega nace SIN_REMITO y sigue el circuito estándar Remito -> Pago.
     */
    public function entregar(Reventa $reventa): Entrega
    {
        if ($reventa->getEstado() == null || $reventa->getEstado()->getCodigoInterno() != ConstanteEstadoReventa::PENDIENTE_ENTREGA) {
            throw new \DomainException('Solo se pueden entregar reventas pendientes de entrega.');
        }

        $pedidoProducto = $reventa->getPedidoProducto();

        $entrega = new Entrega();
        $entrega->setCliente($reventa->getCliente());
        $entrega->setClienteEntrega($reventa->getCliente());
        $entrega->setEntregado(true);
        $entrega->setObservacion('Entrega generada por ' . $reventa . ' (' . $reventa->getDevolucion() . ').');

        $entregaProducto = new EntregaProducto();
        $entregaProducto->setPedidoProducto($pedidoProducto);
        $entregaProducto->setCantidadBandejas($reventa->getCantidadBandejas());
        $entregaProducto->setEsReventa(true);
        $entrega->addEntregaProducto($entregaProducto);

        $estadoEntregaProducto = $this->em->getRepository(EstadoEntregaProducto::class)->findOneBy(['codigoInterno' => ConstanteEstadoEntregaProducto::PENDIENTE]);
        $this->estadoService->cambiarEstadoEntregaProducto($entregaProducto, $estadoEntregaProducto, 'Entrega de reventa.');

        $estadoEntrega = $this->em->getRepository(EstadoEntrega::class)->findOneBy(['codigoInterno' => ConstanteEstadoEntrega::SIN_REMITO]);
        $this->estadoService->cambiarEstadoEntrega($entrega, $estadoEntrega, 'Entrega de reventa.');

        $reventa->setEntregaProducto($entregaProducto);

        $estadoReventa = $this->em->getRepository(EstadoReventa::class)->findOneBy(['codigoInterno' => ConstanteEstadoReventa::ENTREGADA_SIN_REMITO]);
        $this->estadoService->cambiarEstadoReventa($reventa, $estadoReventa, 'Entrega de reventa.');

        $this->em->persist($entrega);
        $this->em->flush();

        return $entrega;
    }

    /**
     * Cancela una reventa. Si ya fue entregada y la entrega no tiene remito,
     * también cancela la entrega. Si tiene remito, solo se puede cancelar si el remito está CANCELADO.
     */
    public function cancelar(Reventa $reventa): void
    {
        if ($reventa->getEstado() != null && $reventa->getEstado()->getCodigoInterno() == ConstanteEstadoReventa::CANCELADA) {
            throw new \DomainException('La reventa ya fue cancelada.');
        }

        if ($reventa->getEstado() != null && in_array($reventa->getEstado()->getCodigoInterno(), [ConstanteEstadoReventa::ENTREGADA_SIN_REMITO, ConstanteEstadoReventa::ENTREGADA_CON_REMITO])) {
            $entrega = $reventa->getEntrega();
            if ($entrega != null && $entrega->getRemito() != null) {
                // Verificar si el remito está cancelado
                if ($entrega->getRemito()->getEstado() != null && $entrega->getRemito()->getEstado()->getCodigoInterno() != ConstanteEstadoRemito::CANCELADO) {
                    throw new \DomainException('La entrega de la reventa tiene un remito activo, no se puede cancelar.');
                }
            }
            if ($entrega != null) {
                $estadoEntrega = $this->em->getRepository(EstadoEntrega::class)->findOneBy(['codigoInterno' => ConstanteEstadoEntrega::CANCELADA]);
                $this->estadoService->cambiarEstadoEntrega($entrega, $estadoEntrega, 'Cancela reventa.');
            }
        }

        $estadoReventa = $this->em->getRepository(EstadoReventa::class)->findOneBy(['codigoInterno' => ConstanteEstadoReventa::CANCELADA]);
        $this->estadoService->cambiarEstadoReventa($reventa, $estadoReventa, 'Cancela reventa.');
        
        // Flush para que el cambio de estado de la reventa se guarde antes de recalcular la devolución
        $this->em->flush();

        $this->actualizarEstadoDevolucion($reventa->getDevolucion(), 'Cancelación de ' . $reventa . '.');

        $this->em->flush();
    }

    /**
     * Descarta la devolución: las bandejas no revendidas dejan de estar disponibles.
     */
    public function descartarDevolucion(Devolucion $devolucion): void
    {
        if ($devolucion->getEstado() != null && $devolucion->getEstado()->getCodigoInterno() == ConstanteEstadoDevolucion::DESCARTADA) {
            throw new \DomainException('La devolución ya fue descartada.');
        }

        if ($devolucion->getCantidadDisponible() <= 0) {
            throw new \DomainException('La devolución no tiene bandejas disponibles para descartar.');
        }

        $estadoDevolucion = $this->em->getRepository(EstadoDevolucion::class)->findOneBy(['codigoInterno' => ConstanteEstadoDevolucion::DESCARTADA]);
        $this->estadoService->cambiarEstadoDevolucion($devolucion, $estadoDevolucion, 'Descarte de ' . $devolucion->getCantidadDisponible() . ' bandejas.');

        $this->em->flush();
    }

    /**
     * Recalcula el estado de la devolución según las reventas activas.
     */
    private function actualizarEstadoDevolucion(Devolucion $devolucion, string $motivo): void
    {
        if ($devolucion->getEstado() != null && $devolucion->getEstado()->getCodigoInterno() == ConstanteEstadoDevolucion::DESCARTADA) {
            return;
        }

        if ($devolucion->getCantidadDisponible() == 0 && $devolucion->getCantidadRevendida() > 0) {
            $codigoInterno = ConstanteEstadoDevolucion::REVENDIDA;
        } elseif ($devolucion->getCantidadRevendida() > 0) {
            $codigoInterno = ConstanteEstadoDevolucion::REVENDIDA_PARCIAL;
        } else {
            $codigoInterno = ConstanteEstadoDevolucion::PENDIENTE;
        }

        if ($devolucion->getEstado() != null && $devolucion->getEstado()->getCodigoInterno() == $codigoInterno) {
            return;
        }

        $estadoDevolucion = $this->em->getRepository(EstadoDevolucion::class)->findOneBy(['codigoInterno' => $codigoInterno]);
        $this->estadoService->cambiarEstadoDevolucion($devolucion, $estadoDevolucion, $motivo);
    }

    /**
     * Distribuye el saldo de una reventa.
     */
    public function distribuirSaldo(Reventa $reventa, float $montoClienteOriginal, float $montoPlantinera, string $token): void
    {
        if ($reventa->getEstado() == null || $reventa->getEstado()->getCodigoInterno() != ConstanteEstadoReventa::ENTREGADA_CON_REMITO) {
            throw new \DomainException('Solo se puede distribuir una reventa entregada con remito.');
        }

        if ($reventa->tieneDistribucionSaldo()) {
            throw new \DomainException('El saldo de esta reventa ya fue distribuido.');
        }

        $remito = $reventa->getEntrega()?->getRemito();
        if ($remito == null || $remito->getEstado()?->getCodigoInterno() == ConstanteEstadoRemito::CANCELADO) {
            throw new \DomainException('La reventa no tiene un remito activo.');
        }

        $total = round($reventa->getMontoDistribuible(), 2);
        if ($montoClienteOriginal <= 0 || $montoPlantinera < 0 || abs(round($montoClienteOriginal + $montoPlantinera, 2) - $total) > 0.001) {
            throw new \DomainException('El importe del cliente debe ser mayor a cero, el de la plantinera no puede ser negativo y ambos deben sumar exactamente el total distribuible.');
        }

        $clienteOriginal = $reventa->getClienteOriginal();
        $cuentaCorriente = $clienteOriginal?->getCuentaCorrienteUsuario();
        if ($cuentaCorriente == null) {
            throw new \DomainException('El cliente original no tiene una cuenta corriente asociada.');
        }

        $this->em->beginTransaction();
        try {
            $movimiento = $this->movimientoService->crear([
                'monto' => number_format($montoClienteOriginal, 2, ',', ''),
                'modoPago' => ConstanteModoPago::AJUSTE,
                'descripcion' => 'Crédito por Reventa N° ' . $reventa->getId() . ' - Devolución N° ' . $reventa->getDevolucion()->getId(),
                'token' => $token,
                'tipoMovimiento' => ConstanteTipoMovimiento::CREDITO_REVENTA,
                'cuentaCorrienteUsuario' => $cuentaCorriente,
            ]);

            $reventa->setMontoClienteOriginal($montoClienteOriginal);
            $reventa->setMontoPlantinera($montoPlantinera);
            $reventa->setFechaDistribucion(new DateTime());
            $reventa->setMovimientoDistribucion($movimiento);
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
