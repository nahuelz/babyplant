<?php

namespace App\Service;

use App\Entity\CuentaCorrientePedido;
use App\Entity\CuentaCorrienteReserva;
use App\Entity\CuentaCorrienteUsuario;
use App\Entity\Movimiento;
use App\Entity\Pago;
use App\Entity\Pedido;
use App\Entity\Remito;
use App\Entity\Reserva;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;

class SituacionClienteService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Carga un usuario con todas sus relaciones optimizadas para la vista de situación cliente.
     * Usa consultas separadas para evitar productos cartesianos.
     */
    public function cargarUsuarioCompleto(int $id): ?Usuario
    {
        // 1. Cargar usuario con cuenta corriente
        $entity = $this->em->createQueryBuilder()
            ->select('u', 'ccu')
            ->from(Usuario::class, 'u')
            ->leftJoin('u.cuentaCorrienteUsuario', 'ccu')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$entity) {
            return null;
        }

        // 2. Pre-cargar pedidos con productos y estados
        $this->precargarPedidos($entity);

        // 3. Pre-cargar reservas con estados
        $this->precargarReservas($entity);

        // 4. Pre-cargar remitos con estados y pagos
        $this->precargarRemitos($entity);

        // 5. Pre-cargar movimientos
        $this->precargarMovimientos($entity);

        return $entity;
    }

    /**
     * Pre-carga pedidos con productos, estados y tipos
     */
    private function precargarPedidos(Usuario $entity): void
    {
        $this->em->createQueryBuilder()
            ->select('p', 'ccp', 'pp', 'epp', 'tv', 'tsp', 'tp', 'tb')
            ->from(Pedido::class, 'p')
            ->leftJoin('p.cuentaCorrientePedido', 'ccp')
            ->leftJoin('p.pedidosProductos', 'pp')
            ->leftJoin('pp.estado', 'epp')
            ->leftJoin('pp.tipoVariedad', 'tv')
            ->leftJoin('tv.tipoSubProducto', 'tsp')
            ->leftJoin('tsp.tipoProducto', 'tp')
            ->leftJoin('pp.tipoBandeja', 'tb')
            ->where('p.cliente = :cliente')
            ->setParameter('cliente', $entity)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pre-carga reservas con estados
     */
    private function precargarReservas(Usuario $entity): void
    {
        $this->em->createQueryBuilder()
            ->select('r', 'ccr', 'er')
            ->from(Reserva::class, 'r')
            ->leftJoin('r.cuentaCorrienteReserva', 'ccr')
            ->leftJoin('r.estado', 'er')
            ->where('r.cliente = :cliente')
            ->setParameter('cliente', $entity)
            ->orderBy('r.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pre-carga remitos con estados, tipo descuento y pagos
     */
    private function precargarRemitos(Usuario $entity): void
    {
        $this->em->createQueryBuilder()
            ->select('rem', 'er', 'td', 'pag', 'mp')
            ->from(Remito::class, 'rem')
            ->leftJoin('rem.estado', 'er')
            ->leftJoin('rem.tipoDescuento', 'td')
            ->leftJoin('rem.pagos', 'pag')
            ->leftJoin('pag.modoPago', 'mp')
            ->where('rem.cliente = :cliente')
            ->setParameter('cliente', $entity)
            ->orderBy('rem.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pre-carga todos los movimientos relacionados al usuario
     */
    private function precargarMovimientos(Usuario $entity): void
    {
        // Movimientos de cuenta corriente del usuario
        if ($entity->getCuentaCorrienteUsuario()) {
            $this->em->createQueryBuilder()
                ->select('m', 'mpago')
                ->from(Movimiento::class, 'm')
                ->leftJoin('m.modoPago', 'mpago')
                ->where('m.cuentaCorrienteUsuario = :ccu')
                ->setParameter('ccu', $entity->getCuentaCorrienteUsuario())
                ->orderBy('m.id', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Movimientos de cuentas corrientes de pedidos
        $pedidoIds = array_map(
            fn($p) => $p->getCuentaCorrientePedido()?->getId(),
            $entity->getPedidos()->toArray()
        );
        $pedidoIds = array_filter($pedidoIds);

        if (!empty($pedidoIds)) {
            $this->em->createQueryBuilder()
                ->select('m', 'mpago')
                ->from(Movimiento::class, 'm')
                ->leftJoin('m.modoPago', 'mpago')
                ->where('IDENTITY(m.cuentaCorrientePedido) IN (:ids)')
                ->setParameter('ids', $pedidoIds)
                ->orderBy('m.id', 'DESC')
                ->getQuery()
                ->getResult();
        }

        // Movimientos de cuentas corrientes de reservas
        $reservaIds = array_map(
            fn($r) => $r->getCuentaCorrienteReserva()?->getId(),
            $entity->getReservas()->toArray()
        );
        $reservaIds = array_filter($reservaIds);

        if (!empty($reservaIds)) {
            $this->em->createQueryBuilder()
                ->select('m', 'mpago')
                ->from(Movimiento::class, 'm')
                ->leftJoin('m.modoPago', 'mpago')
                ->where('IDENTITY(m.cuentaCorrienteReserva) IN (:ids)')
                ->setParameter('ids', $reservaIds)
                ->orderBy('m.id', 'DESC')
                ->getQuery()
                ->getResult();
        }
    }

    /**
     * Crea cuentas corrientes faltantes para el usuario, sus pedidos y reservas
     */
    public function crearCuentasCorrientesFaltantes(Usuario $entity): void
    {
        if ($entity->getCuentaCorrienteUsuario() === null) {
            $cuentaCorrienteUsuario = new CuentaCorrienteUsuario();
            $cuentaCorrienteUsuario->setCliente($entity);
            $entity->setCuentaCorrienteUsuario($cuentaCorrienteUsuario);
            $this->em->persist($cuentaCorrienteUsuario);
        }

        foreach ($entity->getPedidos() as $pedido) {
            if ($pedido->getCuentaCorrientePedido() === null) {
                $cuentaCorrientePedido = new CuentaCorrientePedido();
                $cuentaCorrientePedido->setPedido($pedido);
                $pedido->setCuentaCorrientePedido($cuentaCorrientePedido);
                $this->em->persist($cuentaCorrientePedido);
            }
        }

        foreach ($entity->getReservas() as $reserva) {
            if ($reserva->getCuentaCorrienteReserva() === null) {
                $cuentaCorrienteReserva = new CuentaCorrienteReserva();
                $cuentaCorrienteReserva->setReserva($reserva);
                $reserva->setCuentaCorrienteReserva($cuentaCorrienteReserva);
                $this->em->persist($cuentaCorrienteReserva);
            }
        }

        $this->em->flush();
    }

    /**
     * Obtiene los pagos del cliente ordenados por fecha
     */
    public function obtenerPagos(int $idCliente): array
    {
        return $this->em->createQueryBuilder()
            ->select('p', 'mp')
            ->from(Pago::class, 'p')
            ->join('p.remito', 'r')
            ->leftJoin('p.modoPago', 'mp')
            ->where('IDENTITY(r.cliente) = :idCliente')
            ->setParameter('idCliente', $idCliente)
            ->orderBy('p.fechaCreacion', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
