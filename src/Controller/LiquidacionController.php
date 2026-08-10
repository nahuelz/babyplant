<?php

namespace App\Controller;

use App\Entity\ConceptoLiquidacion;
use App\Entity\Constants\ConstanteEstadoLiquidacion;
use App\Entity\Constants\ConstanteTipoModalidadPago;
use App\Entity\Empleado;
use App\Entity\EstadoLiquidacion;
use App\Entity\Liquidacion;
use App\Entity\PagoEmpleado;
use App\Entity\TipoConceptoLiquidacion;
use App\Form\LiquidacionType;
use App\Form\PagoEmpleadoType;
use App\Service\LiquidacionService;
use App\Util\Decimal;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/liquidacion")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class LiquidacionController extends BaseController
{
    /**
     * @Route("/", name="liquidacion_index", methods={"GET"})
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $periodo = $request->query->get('periodo', (new DateTime())->format('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $periodo = (new DateTime())->format('Y-m');
        }

        $semanas = $this->buildSemanasDelMes($periodo);
        $empleados = $entityManager->getRepository(Empleado::class)->findBy(
            ['activo' => true],
            ['apellido' => 'ASC', 'nombre' => 'ASC']
        );

        $grid = [];
        foreach ($empleados as $empleado) {
            $grid[] = $this->buildFila($empleado, $periodo, $semanas, $entityManager);
        }

        $totalesPorSemana = array_fill(0, count($semanas), '0');
        $totalGeneral = '0';

        foreach ($grid as $fila) {
            foreach ($fila['semanas'] as $index => $semana) {
                if ($semana['liquidacion']) {
                    $totalesPorSemana[$index] = Decimal::add($totalesPorSemana[$index], (string) $semana['liquidacion']->getTotalAPagar(), 2);
                }
            }

            if ($fila['resumen']) {
                $totalGeneral = Decimal::add($totalGeneral, (string) $fila['resumen']->getTotalAPagar(), 2);
            }
        }

        return $this->render('liquidacion/index.html.twig', [
            'periodo' => $periodo,
            'semanas' => $semanas,
            'grid' => $grid,
            'totales_semanas' => $totalesPorSemana,
            'total_general' => $totalGeneral,
        ]);
    }

    /**
     * @Route("/generar", name="liquidacion_generar", methods={"POST"})
     */
    public function generar(Request $request, EntityManagerInterface $entityManager): Response
    {
        $periodo = $request->request->get('periodo', (new DateTime())->format('Y-m'));

        if (!$this->isCsrfTokenValid('generar_' . $periodo, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $periodo]);
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $periodo = (new DateTime())->format('Y-m');
        }

        $semanas = $this->buildSemanasDelMes($periodo);
        $empleados = $entityManager->getRepository(Empleado::class)->findBy(['activo' => true]);
        $estadoBorrador = $entityManager->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::BORRADOR);

        if (!$estadoBorrador) {
            $this->addFlash('error', 'No existe el estado BORRADOR.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $periodo]);
        }

        $creadas = 0;
        foreach ($empleados as $empleado) {
            $modalidad = $empleado->getModalidadPago();
            if (!$modalidad) {
                continue;
            }

            $esSemanal = $modalidad->getCodigoInterno() === ConstanteTipoModalidadPago::SEMANAL;
            $esMensual = $modalidad->getCodigoInterno() === ConstanteTipoModalidadPago::MENSUAL;

            if ($esSemanal) {
                $rangoMes = [
                    'inicio' => new DateTime($periodo . '-01'),
                    'fin' => (new DateTime($periodo . '-01'))->modify('last day of this month'),
                ];

                $this->crearLiquidacionSiNoExiste($empleado, $periodo, $rangoMes, $estadoBorrador, $modalidad, $entityManager);

                // Necesario para que el resumen tenga id antes de buscarlo y vincular semanas hijas
                $entityManager->flush();

                $padre = $entityManager->getRepository(Liquidacion::class)->findOneBy([
                    'empleado' => $empleado,
                    'fechaDesde' => $rangoMes['inicio'],
                    'fechaHasta' => $rangoMes['fin'],
                    'padre' => null,
                ]);

                if ($padre) {
                    foreach ($semanas as $semana) {
                        if ($this->crearLiquidacionSiNoExiste($empleado, $periodo, $semana, $estadoBorrador, $modalidad, $entityManager, $padre)) {
                            $creadas++;
                        }
                    }
                }
            } elseif ($esMensual) {
                $semanaMes = [
                    'inicio' => new DateTime($periodo . '-01'),
                    'fin' => (new DateTime($periodo . '-01'))->modify('last day of this month'),
                ];
                if ($this->crearLiquidacionSiNoExiste($empleado, $periodo, $semanaMes, $estadoBorrador, $modalidad, $entityManager)) {
                    $creadas++;
                }
            }
        }

        $entityManager->flush();

        $this->addFlash('success', 'Se generaron ' . $creadas . ' liquidaciones en borrador.');

        return $this->redirectToRoute('liquidacion_index', ['periodo' => $periodo]);
    }

    /**
     * @Route("/{id}", name="liquidacion_show", methods={"GET"})
     */
    public function show(Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        $totalPagado = $this->getTotalPagado($liquidacion);
        $restante = Decimal::sub((string) $liquidacion->getTotalAPagar(), $totalPagado, 2);

        $pago = new PagoEmpleado();
        $pago->setFecha(new DateTime());
        $pago->setImporte($restante);

        $pagoForm = $this->createForm(PagoEmpleadoType::class, $pago, [
            'action' => $this->generateUrl('liquidacion_pagar', ['id' => $liquidacion->getId()]),
            'method' => 'POST',
        ]);

        $incluirSueldo = !($liquidacion->getDetallesSemanales()->count() > 0);
        $incluirConceptos = $liquidacion->getPadre() === null;

        $form = null;
        if ($this->puedeEditarContenido($liquidacion)) {
            $form = $this->createForm(LiquidacionType::class, $liquidacion, [
                'action' => $this->generateUrl('liquidacion_guardar', ['id' => $liquidacion->getId()]),
                'method' => 'POST',
                'incluir_sueldo' => $incluirSueldo,
                'incluir_conceptos' => $incluirConceptos,
            ]);
        }

        return $this->render('liquidacion/show.html.twig', [
            'liquidacion' => $liquidacion,
            'form' => $form ? $form->createView() : null,
            'pagoForm' => $pagoForm->createView(),
            'editable' => $this->puedeEditarContenido($liquidacion),
            'totalPagado' => $totalPagado,
            'restante' => $restante,
        ]);
    }

    /**
     * @Route("/{id}/guardar", name="liquidacion_guardar", methods={"POST"})
     */
    public function guardar(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->puedeEditarContenido($liquidacion)) {
            $this->addFlash('error', 'La liquidación no puede editarse en este estado.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $incluirSueldo = !($liquidacion->getDetallesSemanales()->count() > 0);
        $incluirConceptos = $liquidacion->getPadre() === null;

        $form = $this->createForm(LiquidacionType::class, $liquidacion, [
            'incluir_sueldo' => $incluirSueldo,
            'incluir_conceptos' => $incluirConceptos,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($incluirConceptos) {
                $submittedData = $request->request->get('liquidacion', []);
                $submittedConceptos = $submittedData['conceptos'] ?? [];

                $conceptoRepo = $entityManager->getRepository(ConceptoLiquidacion::class);
                $tipoConceptoRepo = $entityManager->getRepository(TipoConceptoLiquidacion::class);

                $existingById = [];
                foreach ($liquidacion->getConceptos() as $concepto) {
                    $existingById[$concepto->getId()] = $concepto;
                }

                $submittedIds = [];
                $newCollection = new \Doctrine\Common\Collections\ArrayCollection();

                foreach ($submittedConceptos as $data) {
                    $id = !empty($data['id']) ? (int) $data['id'] : null;

                    if ($id && isset($existingById[$id])) {
                        $concepto = $existingById[$id];
                        $submittedIds[] = $id;
                    } else {
                        $concepto = new ConceptoLiquidacion();
                    }

                    $tipoConcepto = !empty($data['tipoConceptoLiquidacion']) ? $tipoConceptoRepo->find($data['tipoConceptoLiquidacion']) : null;
                    $cantidad = str_replace(['.', ','], ['', '.'], $data['cantidad'] ?? '0');
                    $valorUnitario = str_replace(['.', ','], ['', '.'], $data['valorUnitario'] ?? '0');

                    $concepto->setTipoConceptoLiquidacion($tipoConcepto);
                    $concepto->setDescripcion($data['descripcion'] ?? null);
                    $concepto->setCantidad($cantidad);
                    $concepto->setValorUnitario($valorUnitario);
                    $concepto->setImporte(Decimal::mul($cantidad, $valorUnitario, 2));
                    $concepto->setLiquidacion($liquidacion);

                    $newCollection->add($concepto);
                }

                foreach ($existingById as $id => $concepto) {
                    if (!in_array($id, $submittedIds, true)) {
                        $entityManager->remove($concepto);
                    }
                }

                $liquidacion->getConceptos()->clear();
                foreach ($newCollection as $concepto) {
                    $liquidacion->addConcepto($concepto);
                }
            }

            $estadoBorrador = $entityManager->getRepository(EstadoLiquidacion::class)
                ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::BORRADOR);

            if ($estadoBorrador) {
                $liquidacion->setEstado($estadoBorrador);
            }

            $liquidacion->recalcularTotal();

            $padre = $liquidacion->getPadre();
            if ($padre !== null) {
                $padre->recalcularTotal();
            }

            $entityManager->flush();

            $this->addFlash('success', 'Liquidación guardada correctamente.');

            $redirectId = $padre !== null ? $padre->getId() : $liquidacion->getId();
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        $this->addFlash('error', 'Verifique los datos ingresados.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/calcular", name="liquidacion_calcular", methods={"POST"})
     */
    public function calcular(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('calcular_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        if (!$this->puedeEditarContenido($liquidacion)) {
            $this->addFlash('error', 'La liquidación no puede calcularse en este estado.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $liquidacionService->calcular($liquidacion);
        $entityManager->flush();

        $this->addFlash('success', 'Liquidación calculada correctamente.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/aprobar", name="liquidacion_aprobar", methods={"POST"})
     */
    public function aprobar(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('aprobar_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        if (!$liquidacion->getEstado() || $liquidacion->getEstado()->getCodigoInterno() !== ConstanteEstadoLiquidacion::CALCULADA) {
            $this->addFlash('error', 'La liquidación debe estar calculada para poder aprobarse.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $liquidacionService->aprobar($liquidacion);
        $entityManager->flush();

        $this->addFlash('success', 'Liquidación aprobada correctamente.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/pagar", name="liquidacion_pagar", methods={"POST"})
     */
    public function pagar(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        $estadoCodigo = $liquidacion->getEstado() ? $liquidacion->getEstado()->getCodigoInterno() : null;

        if (!$estadoCodigo || ($estadoCodigo !== ConstanteEstadoLiquidacion::APROBADA && $estadoCodigo !== ConstanteEstadoLiquidacion::PAGADA)) {
            $this->addFlash('error', 'La liquidación debe estar aprobada para registrar pagos.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $pago = new PagoEmpleado();
        $pago->setLiquidacion($liquidacion);

        $form = $this->createForm(PagoEmpleadoType::class, $pago);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $totalPagado = $this->getTotalPagado($liquidacion);
            $restante = Decimal::sub((string) $liquidacion->getTotalAPagar(), (string) $totalPagado, 2);

            if (Decimal::comp((string) $pago->getImporte(), $restante, 2) > 0) {
                $this->addFlash('error', 'El pago no puede superar el restante a abonar ($' . $this->formatMoney($restante) . ').');
                return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
            }

            $liquidacion->addPago($pago);
            $entityManager->persist($pago);

            $totalPagado = Decimal::add((string) $totalPagado, (string) $pago->getImporte(), 2);

            if (Decimal::comp($totalPagado, (string) $liquidacion->getTotalAPagar(), 2) >= 0) {
                $liquidacionService->marcarComoPagada($liquidacion);
                $this->addFlash('success', 'Pago registrado. La liquidación quedó pagada.');
            } else {
                $restante = Decimal::sub((string) $liquidacion->getTotalAPagar(), $totalPagado, 2);
                $this->addFlash('success', 'Pago registrado. Resta abonar $' . $this->formatMoney($restante) . '.');
            }

            $entityManager->flush();

            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $this->addFlash('error', 'Verifique los datos del pago.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/anular", name="liquidacion_anular", methods={"POST"})
     */
    public function anular(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('anular_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        if ($liquidacion->getEstado() && $liquidacion->getEstado()->getCodigoInterno() === ConstanteEstadoLiquidacion::ANULADA) {
            $this->addFlash('error', 'La liquidación ya se encuentra anulada.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $motivo = $request->request->get('motivo', 'Liquidación anulada.');

        $liquidacionService->anular($liquidacion, $motivo);
        $entityManager->flush();

        $this->addFlash('success', 'Liquidación anulada correctamente.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/guardar-semana", name="liquidacion_guardar_semana", methods={"POST"})
     */
    public function guardarSemana(Request $request, Liquidacion $liquidacion, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('guardar_semana_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
        }

        if (!$this->puedeEditarContenido($liquidacion)) {
            $this->addFlash('error', 'La liquidación no puede editarse en este estado.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
        }

        if ($liquidacion->getPadre() === null) {
            $this->addFlash('error', 'Solo se pueden editar liquidaciones semanales desde esta acción.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
        }

        $sueldoBruto = str_replace(',', '.', $request->request->get('sueldoBruto', '0'));
        $deducciones = str_replace(',', '.', $request->request->get('deducciones', '0'));

        $liquidacion->setSueldoBruto($sueldoBruto);
        $liquidacion->setDeducciones($deducciones);
        $liquidacion->recalcularTotal();

        $padre = $liquidacion->getPadre();
        $padre->recalcularTotal();

        $entityManager->flush();

        $this->addFlash('success', 'Liquidación semanal guardada correctamente.');

        return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
    }

    private function buildSemanasDelMes(string $periodo): array
    {
        $semanas = [];
        $inicio = new DateTime($periodo . '-01');
        $finMes = clone $inicio;
        $finMes->modify('last day of this month');

        $numero = 1;
        while ($inicio <= $finMes) {
            $fin = clone $inicio;
            $fin->modify('+6 days');

            if ($fin > $finMes) {
                $fin = clone $finMes;
            }

            $semanas[] = [
                'numero' => $numero,
                'inicio' => clone $inicio,
                'fin' => clone $fin,
                'label' => $inicio->format('d/m') . ' - ' . $fin->format('d/m'),
            ];

            $inicio = clone $fin;
            $inicio->modify('+1 day');
            $numero++;
        }

        return $semanas;
    }

    private function buildFila(Empleado $empleado, string $periodo, array $semanas, EntityManagerInterface $entityManager): array
    {
        $liquidaciones = $entityManager->getRepository(Liquidacion::class)->findBy([
            'empleado' => $empleado,
            'periodo' => $periodo,
        ]);

        $resumen = null;
        $detalles = [];
        foreach ($liquidaciones as $liquidacion) {
            if ($liquidacion->getPadre() === null) {
                $resumen = $liquidacion;
            } else {
                $detalles[$liquidacion->getFechaHasta()->format('Y-m-d')] = $liquidacion;
            }
        }

        $fila = [
            'empleado' => $empleado,
            'resumen' => $resumen,
            'modalidad' => $empleado->getModalidadPago() ? $empleado->getModalidadPago()->getNombre() : '-',
            'semanas' => [],
            'total' => '0',
        ];

        foreach ($semanas as $semana) {
            $fila['semanas'][] = [
                'liquidacion' => null,
                'valor' => null,
            ];
        }

        foreach ($detalles as $key => $liquidacion) {
            foreach ($semanas as $index => $semana) {
                if ($semana['fin']->format('Y-m-d') === $key) {
                    $fila['semanas'][$index]['liquidacion'] = $liquidacion;
                    $fila['semanas'][$index]['valor'] = $this->formatMoney($liquidacion->getTotalAPagar());
                    break;
                }
            }
        }

        $fila['total'] = $resumen !== null
            ? $this->formatMoney($resumen->getTotalAPagar())
            : $this->formatMoney('0');

        return $fila;
    }

    private function crearLiquidacionSiNoExiste(
        Empleado $empleado,
        string $periodo,
        array $rango,
        EstadoLiquidacion $estadoBorrador,
        \App\Entity\TipoModalidadPago $modalidad,
        EntityManagerInterface $entityManager,
        ?Liquidacion $padre = null
    ): bool {
        $criteria = [
            'empleado' => $empleado,
            'fechaDesde' => $rango['inicio'],
            'fechaHasta' => $rango['fin'],
        ];

        if ($padre === null) {
            $criteria['padre'] = null;
        } else {
            $criteria['padre'] = $padre;
        }

        $existente = $entityManager->getRepository(Liquidacion::class)->findOneBy($criteria);

        if ($existente) {
            return false;
        }

        $liquidacion = new Liquidacion();
        $liquidacion->setEmpleado($empleado);
        $liquidacion->setPeriodo($periodo);
        $liquidacion->setFechaDesde($rango['inicio']);
        $liquidacion->setFechaHasta($rango['fin']);
        $liquidacion->setTipoModalidadPago($modalidad);
        $liquidacion->setEstado($estadoBorrador);
        $liquidacion->setSueldoBruto(0);
        $liquidacion->setDeducciones(0);
        $liquidacion->setTotalAPagar(0);

        if ($padre !== null) {
            $padre->addDetalleSemanal($liquidacion);
        }

        $entityManager->persist($liquidacion);

        return true;
    }

    private function getTotalPagado(Liquidacion $liquidacion): string
    {
        $total = '0';
        foreach ($liquidacion->getPagos() as $pago) {
            $total = Decimal::add($total, (string) $pago->getImporte(), 2);
        }
        return $total;
    }

    private function puedeEditarContenido(Liquidacion $liquidacion): bool
    {
        $relevante = $liquidacion->getPadre() ?? $liquidacion;
        $estado = $relevante->getEstado();
        if (!$estado) {
            return true;
        }

        return in_array($estado->getCodigoInterno(), [
            ConstanteEstadoLiquidacion::BORRADOR,
            ConstanteEstadoLiquidacion::CALCULADA,
        ], true);
    }

    private function formatMoney($valor): string
    {
        $valor = (float) $valor;
        return '$' . number_format($valor, 2, ',', '.');
    }

}
