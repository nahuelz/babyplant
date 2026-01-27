<?php

namespace App\Service;

use App\Entity\Constants\ConstanteTipoMovimiento;
use App\Entity\Movimiento;
use App\Entity\ModoPago;
use App\Entity\TipoMovimiento;
use Doctrine\ORM\EntityManagerInterface;

class MovimientoService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Crea un movimiento genérico (adelanto, ingreso, ajuste, etc.)
     */
    public function crear(array $data): Movimiento
    {

        $this->validarMonto($data);

        $this->validarToken($data['token']);

        $monto = $this->normalizarMonto($data['monto']);

        $this->validarAjuste($data, $monto);

        $monto = $this->aplicarSignoPorTipo(
            $monto,
            $data['tipoMovimiento']
        );

        $modoPago = $this->em->getRepository(ModoPago::class)->findOneByCodigoInterno($data['modoPago']);

        $tipoMovimiento = $this->em->getRepository(TipoMovimiento::class)->findOneByCodigoInterno($data['tipoMovimiento']);

        $movimiento = new Movimiento();
        $movimiento->setMonto($monto);
        $movimiento->setModoPago($modoPago);
        $movimiento->setDescripcion($data['descripcion'] ?? null);
        $movimiento->setTipoMovimiento($tipoMovimiento);
        $movimiento->setToken($data['token']);

        $this->vincularContexto($movimiento, $data);

        $this->em->persist($movimiento);
        $this->em->flush();

        return $movimiento;
    }

    private function validarToken(string $token): void
    {
        if ($this->em->getRepository(Movimiento::class)
            ->findOneBy(['token' => $token])) {
            throw new \DomainException('Este movimiento ya fue procesado');
        }
    }

    private function normalizarMonto(string $monto): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $monto);
    }

    private function vincularContexto(Movimiento $movimiento, array $data): void
    {
        if (!empty($data['cuentaCorrienteUsuario'])) {
            $cc = $data['cuentaCorrienteUsuario'];
            $movimiento->setCuentaCorrienteUsuario($cc);
            $cc->addMovimiento($movimiento);
            $movimiento->setSaldoCuenta($cc->getSaldo());
        }

        if (!empty($data['cuentaCorrienteUsuarioPedido'])) {
            $cuentaCorrientePedido = $data['cuentaCorrienteUsuarioPedido'];
            $movimiento->setPedido($cuentaCorrientePedido->getPedido());
            $movimiento->setCuentaCorrientePedido($cuentaCorrientePedido);
            $cuentaCorrientePedido->addMovimiento($movimiento);
            $movimiento->setSaldoCuenta($cuentaCorrientePedido->getSaldo());
        }

        if (!empty($data['reserva'])) {
            $reserva = $data['reserva'];
            $movimiento->setReserva($reserva);
            $reserva->getCuentaCorrienteReserva()->addMovimiento($movimiento);
            $movimiento->setSaldoCuenta($reserva->getCuentaCorrienteReserva()->getSaldo()
            );
        }
    }

    private function validarMonto($movimientoData){
        if (!isset($movimientoData['monto']) || $movimientoData['monto'] <= 0) {
            throw new \DomainException('El monto debe ser mayor a 0');
        }
    }

    private function aplicarSignoPorTipo(float $monto, int $tipoMovimiento): float
    {
        return match ($tipoMovimiento) {
            ConstanteTipoMovimiento::AJUSTE_RESERVA, ConstanteTipoMovimiento::AJUSTE_CC, ConstanteTipoMovimiento::AJUSTE_PEDIDO => -abs($monto),
            default => $monto,
        };
    }

    private function validarAjuste(array $data, float $monto): void
    {
        if (
            $data['tipoMovimiento'] === ConstanteTipoMovimiento::AJUSTE_RESERVA
            && !empty($data['reserva'])
            && !$data['reserva']->puedeAjustarse($monto)
        ) {
            throw new \DomainException('El monto supera el saldo de la reserva');
        }

        if (
            $data['tipoMovimiento'] === ConstanteTipoMovimiento::AJUSTE_CC
            && !empty($data['cuentaCorrienteUsuario'])
            && !$data['cuentaCorrienteUsuario']->puedeAjustarse($monto)
        ) {
            throw new \DomainException('Saldo insuficiente en la cuenta corriente');
        }

        if (
            $data['tipoMovimiento'] === ConstanteTipoMovimiento::AJUSTE_PEDIDO
            && !empty($data['cuentaCorrientePedido'])
            && !$data['cuentaCorrientePedido']->puedeAjustarse($monto)
        ) {
            throw new \DomainException('Saldo insuficiente en la cuenta corriente del pedido');
        }
    }


}
