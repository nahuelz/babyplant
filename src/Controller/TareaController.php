<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoTarea;
use App\Entity\EstadoTarea;
use App\Entity\EstadoTareaHistorico;
use App\Entity\Notificacion;
use App\Entity\PedidoProducto;
use App\Entity\Tarea;
use App\Entity\TareaAsignacion;
use App\Entity\Usuario;
use App\Form\TareaAsignarType;
use App\Form\TareaAvanceType;
use App\Form\TareaType;
use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/tarea")
 */
class TareaController extends BaseController
{
    #[Route('/empleado', name: 'tarea_empleado_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function indexEmpleado(): Response
    {
        return $this->render('tarea/index_empleado.html.twig', parent::baseIndexAction());
    }

    #[Route('/empleado/mis_tareas_table/', name: 'tarea_empleado_mis_tareas_table', methods: ['GET|POST'])]
    #[IsGranted('ROLE_USER')]
    public function misTareasTableAction(Request $request): Response
    {
        $columnDefinition = [
            ['field' => 'descripcion', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'estado', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false],
        ];

        $renderPage = 'tarea/index_table_empleado_mis.html.twig';
        return parent::baseIndexTableAction(
            $request,
            $columnDefinition,
            'App\Entity\Tarea',
            null,
            null,
            $renderPage,
            [],
            [],
            true
        );
    }

    #[Route('/empleado/disponibles_table/', name: 'tarea_empleado_disponibles_table', methods: ['GET|POST'])]
    #[IsGranted('ROLE_USER')]
    public function disponiblesTableAction(Request $request): Response
    {
        $columnDefinition = [
            ['field' => 'descripcion', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'usuarioCreacion', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false],
        ];

        $renderPage = 'tarea/index_table_empleado_disponibles.html.twig';
        return parent::baseIndexTableAction(
            $request,
            $columnDefinition,
            'App\Entity\Tarea',
            null,
            null,
            $renderPage,
            [],
            [],
            true
        );
    }

    #[Route('/{id}/tomar', name: 'tarea_tomar', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function tomar(Request $request, Tarea $tarea, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('tarea_tomar_' . $tarea->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        $em->beginTransaction();
        try {
            $em->lock($tarea, LockMode::PESSIMISTIC_WRITE);
            $em->refresh($tarea);

            $codigo = (int) $tarea->getEstado()->getCodigoInterno();
            $estadosDisponibles = [
                ConstanteEstadoTarea::NUEVA,
                ConstanteEstadoTarea::ASIGNADA,
            ];
            if (
                !in_array($codigo, $estadosDisponibles, true)
                || $tarea->getEmpleados()->contains($this->getUser())
            ) {
                throw new \RuntimeException('La tarea ya no está disponible.');
            }

            $estadoAsignada = $em->getRepository(EstadoTarea::class)
                ->findOneByCodigoInterno(ConstanteEstadoTarea::ASIGNADA);

            $tarea->addEmpleado($this->getUser());
            $this->iniciarAsignacion($tarea, $this->getUser(), $em);
            if ($codigo === ConstanteEstadoTarea::NUEVA) {
                $tarea->setAsignadoPor($this->getUser());
                $tarea->setAsignadoEn(new DateTime());
            }

            $this->estadoService->cambiarEstadoTarea($tarea, $estadoAsignada, 'Tomada por empleado');

            $em->flush();
            $em->commit();

            $this->addFlash('success', 'Tarea tomada correctamente.');
        } catch (\Throwable $e) {
            $em->rollback();
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute($this->getTareaRedirectRoute());
    }

    #[Route('/{id}/finalizar', name: 'tarea_finalizar', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function finalizar(Request $request, Tarea $tarea, EntityManagerInterface $em): Response
    {
        if (!$tarea->getEmpleados()->contains($this->getUser())) {
            throw new AccessDeniedException('No podés finalizar una asignación que no te pertenece.');
        }

        $form = $this->createForm(TareaAvanceType::class, [
            'porcentajeAvance' => $tarea->getPorcentajeAvance(),
            'observacionAvance' => $tarea->getObservacionAvance(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $porcentaje = (int) $form->get('porcentajeAvance')->getData();
            $observacion = $form->get('observacionAvance')->getData();
            $ahora = new DateTime();

            $this->cerrarAsignacion($tarea, $this->getUser(), $ahora, $porcentaje, $observacion);
            $tarea->removeEmpleado($this->getUser());
            $tarea->setPorcentajeAvance($porcentaje);
            $tarea->setObservacionAvance($observacion);

            if ($porcentaje === 100) {
                foreach ($tarea->getEmpleados()->toArray() as $empleado) {
                    $this->cerrarAsignacion($tarea, $empleado, $ahora, 100, $observacion);
                    $tarea->removeEmpleado($empleado);
                }
                $tarea->setTerminadoEn($ahora);
                $estado = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::TERMINADA);
                $motivo = 'Finalizada por empleado';
            } elseif ($tarea->getEmpleados()->isEmpty()) {
                $estado = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::NUEVA);
                $motivo = 'Avance informado; sin empleados asignados';
            } else {
                $estado = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::ASIGNADA);
                $motivo = 'Avance informado por empleado';
            }

            $this->estadoService->cambiarEstadoTarea($tarea, $estado, $motivo);
            $em->flush();

            $this->addFlash('success', 'Avance registrado correctamente.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        return $this->renderForm('tarea/_avance_form.html.twig', [
            'form' => $form,
            'tarea' => $tarea,
        ]);
    }

    #[Route('/', name: 'tarea_index', methods: ['GET'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function index(): Response
    {
        return $this->render('tarea/index.html.twig', array_merge(
            parent::baseIndexAction(),
            [
                'empleadoSelect' => $this->getSelectService()->getEmpleadoFilter(),
                'indicadorTareaData' => $this->getIndicadorTareaData(),
                'actividadReciente' => $this->getActividadRecienteData(),
            ]
        ));
    }

    #[Route('/index_table/', name: 'tarea_table', methods: ['GET|POST'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function indexTableAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new \DateInterval('P1Y'));
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $idEmpleado = $request->get('idEmpleado') ?: null;

        $qb = $em->getRepository(Tarea::class)->createQueryBuilder('t')
            ->where('t.fechaBaja IS NULL')
            ->andWhere('t.fechaCreacion >= :fechaDesde')
            ->andWhere('t.fechaCreacion <= :fechaHasta')
            ->setParameter('fechaDesde', $fechaDesde)
            ->setParameter('fechaHasta', $fechaHasta);

        if ($idEmpleado) {
            $qb->innerJoin('t.empleados', 'empleado')
                ->andWhere('empleado.id = :idEmpleado')
                ->setParameter('idEmpleado', $idEmpleado);
        }

        $entities = $qb->getQuery()->getResult();

        return $this->render('tarea/index_table.html.twig', [
            'entities' => $entities,
        ]);
    }

    #[Route('/new', name: 'tarea_new', methods: ['GET'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function new(): Response
    {
        return $this->renderForm('tarea/new.html.twig', [
            'form' => $this->createForm(TareaType::class, new Tarea()),
        ]);
    }

    #[Route('/create', name: 'tarea_create', methods: ['POST'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $tarea = new Tarea();
        $form = $this->createForm(TareaType::class, $tarea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fechaProgramada = $form->get('fechaProgramada')->getData();
            if ($fechaProgramada) {
                $tarea->setFechaProgramada(DateTime::createFromFormat('d/m/Y', $fechaProgramada));
            } else {
                $tarea->setFechaProgramada(null);
            }

            $empleados = $tarea->getEmpleados();
            $asignada = !$empleados->isEmpty();

            if ($asignada) {
                $tarea->setAsignadoPor($this->getUser());
                $tarea->setAsignadoEn(new DateTime());
                $estadoAsignada = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::ASIGNADA);
                $this->estadoService->cambiarEstadoTarea($tarea, $estadoAsignada, 'Tarea creada y asignada');
            } else {
                $estadoNueva = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::NUEVA);
                $this->estadoService->cambiarEstadoTarea($tarea, $estadoNueva, 'Tarea creada');
            }

            $em->persist($tarea);
            foreach ($empleados as $empleado) {
                $this->iniciarAsignacion($tarea, $empleado, $em);
            }
            $em->flush();

            if ($asignada) {
                foreach ($empleados as $empleado) {
                    $this->notificarAsignacion($tarea, $empleado, $em);
                }
                $em->flush();
            }

            $this->addFlash('success', 'Tarea creada correctamente.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        return $this->renderForm('tarea/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'tarea_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(Tarea $tarea): Response
    {
        $this->verificarAccesoTarea($tarea);

        return $this->render('tarea/show.html.twig', [
            'tarea' => $tarea,
            'historicoEstados' => $tarea->getHistoricoEstados(),
        ]);
    }

    #[Route('/{id}/historico_estados', name: 'tarea_historico_estado', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function showHistoricoEstadoAction(Tarea $tarea): Response
    {
        $this->verificarAccesoTarea($tarea);

        return $this->render('tarea/historico_estados.html.twig', [
            'entity' => $tarea,
            'historicoEstados' => $tarea->getHistoricoEstados(),
        ]);
    }

    #[Route('/{id}/edit', name: 'tarea_edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function edit(Request $request, Tarea $tarea): Response
    {
        $form = $this->createForm(TareaType::class, $tarea);
        if (!$request->isXmlHttpRequest() && $tarea->getFechaProgramada()) {
            $form->get('fechaProgramada')->setData($tarea->getFechaProgramada()->format('d/m/Y'));
        }
        if ($request->isXmlHttpRequest()) {
            $form->remove('fechaProgramada');
            $form->remove('empleados');
        }

        $template = $request->isXmlHttpRequest() ? 'tarea/_editar_form.html.twig' : 'tarea/new.html.twig';

        return $this->renderForm($template, [
            'form' => $form,
            'tarea' => $tarea,
        ]);
    }

    #[Route('/{id}/update', name: 'tarea_update', methods: ['GET', 'POST', 'PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function update(Request $request, Tarea $tarea, EntityManagerInterface $em): Response
    {
        $codigo = (int) $tarea->getEstado()->getCodigoInterno();
        if (!in_array($codigo, [ConstanteEstadoTarea::NUEVA, ConstanteEstadoTarea::ASIGNADA], true)) {
            $this->addFlash('error', 'No se puede editar una tarea terminada o cancelada.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        $form = $this->createForm(TareaType::class, $tarea);
        if ($request->isXmlHttpRequest()) {
            $form->remove('fechaProgramada');
            $form->remove('empleados');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$request->isXmlHttpRequest()) {
                $fechaProgramada = $form->get('fechaProgramada')->getData();
                if ($fechaProgramada) {
                    $tarea->setFechaProgramada(DateTime::createFromFormat('d/m/Y', $fechaProgramada));
                } else {
                    $tarea->setFechaProgramada(null);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Tarea actualizada correctamente.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['result' => 'OK']);
            }
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = ($error->getOrigin() ? $error->getOrigin()->getName() . ': ' : '') . $error->getMessage();
        }

        $template = $request->isXmlHttpRequest() ? 'tarea/_editar_form.html.twig' : 'tarea/new.html.twig';
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['errors' => $errors], 422);
        }

        return $this->renderForm($template, ['form' => $form, 'tarea' => $tarea]);
    }

    #[Route('/{id}/asignar', name: 'tarea_asignar', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function asignar(Request $request, Tarea $tarea, EntityManagerInterface $em): Response
    {
        $codigo = (int) $tarea->getEstado()->getCodigoInterno();
        if (!in_array($codigo, [ConstanteEstadoTarea::NUEVA, ConstanteEstadoTarea::ASIGNADA], true)) {
            $this->addFlash('error', 'Solo se pueden asignar tareas en estado Nueva o Asignada.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        $form = $this->createForm(TareaAsignarType::class, [
            'empleados' => $tarea->getEmpleados()->toArray(),
        ]);
        $form->handleRequest($request);
        $isAjax = $request->isXmlHttpRequest();

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Usuario[] $empleados */
            $empleados = $form->get('empleados')->getData();
            $empleadosAnteriores = $tarea->getEmpleados()->toArray();

            foreach ($empleadosAnteriores as $empleadoAnterior) {
                if (!in_array($empleadoAnterior, $empleados, true)) {
                    $this->cerrarAsignacion($tarea, $empleadoAnterior, new DateTime());
                }
                $tarea->removeEmpleado($empleadoAnterior);
            }
            foreach ($empleados as $empleado) {
                $tarea->addEmpleado($empleado);
                if (!in_array($empleado, $empleadosAnteriores, true)) {
                    $this->iniciarAsignacion($tarea, $empleado, $em);
                }
            }

            if (count($empleados) === 0) {
                $tarea->setAsignadoPor(null);
                $tarea->setAsignadoEn(null);

                $estadoNueva = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::NUEVA);
                $this->estadoService->cambiarEstadoTarea($tarea, $estadoNueva, 'Desasignada por encargado');
            } else {
                $estadoAsignada = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::ASIGNADA);

                $tarea->setAsignadoPor($this->getUser());
                $tarea->setAsignadoEn(new DateTime());

                $motivo = $codigo === ConstanteEstadoTarea::ASIGNADA
                    ? 'Reasignada por encargado'
                    : 'Asignada por encargado';

                $this->estadoService->cambiarEstadoTarea($tarea, $estadoAsignada, $motivo);
                foreach ($empleados as $empleado) {
                    if (!in_array($empleado, $empleadosAnteriores, true)) {
                        $this->notificarAsignacion($tarea, $empleado, $em);
                    }
                }
            }

            $em->flush();

            if ($isAjax) {
                return new JsonResponse(['result' => 'OK']);
            }

            $this->addFlash('success', 'Tarea actualizada correctamente.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        $template = $isAjax ? 'tarea/_asignar_form.html.twig' : 'tarea/asignar.html.twig';
        if ($isAjax && $form->isSubmitted() && !$form->isValid()) {
            return $this->render($template, [
                'form' => $form->createView(),
                'tarea' => $tarea,
            ], new Response('', 422));
        }

        return $this->renderForm($template, [
            'form' => $form,
            'tarea' => $tarea,
        ]);
    }

    #[Route('/{id}/cancelar', name: 'tarea_cancelar', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function cancelar(Request $request, Tarea $tarea, EntityManagerInterface $em): Response
    {
        $codigo = (int) $tarea->getEstado()->getCodigoInterno();
        if (!in_array($codigo, [ConstanteEstadoTarea::NUEVA, ConstanteEstadoTarea::ASIGNADA], true)) {
            $this->addFlash('error', 'Solo se pueden cancelar tareas en estado Nueva o Asignada.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        if (!$this->isCsrfTokenValid('tarea_cancelar_' . $tarea->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        $estadoCancelada = $em->getRepository(EstadoTarea::class)
            ->findOneByCodigoInterno(ConstanteEstadoTarea::CANCELADA);

        $tarea->setCanceladoEn(new DateTime());
        foreach ($tarea->getEmpleados()->toArray() as $empleado) {
            $this->cerrarAsignacion($tarea, $empleado, $tarea->getCanceladoEn());
            $tarea->removeEmpleado($empleado);
        }

        $this->estadoService->cambiarEstadoTarea($tarea, $estadoCancelada, 'Cancelada por encargado');

        $em->flush();

        $this->addFlash('success', 'Tarea cancelada correctamente.');
        return $this->redirectToRoute($this->getTareaRedirectRoute());
    }

    #[Route('/tiles/data/', name: 'tarea_tiles_data', methods: ['POST'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function tilesDataAction(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();

        $fechaDesde = $request->request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new \DateInterval('P1Y'));
        $fechaHasta = $request->request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $idEmpleado = $request->request->get('idEmpleado') ?: null;

        $qb = $em->getRepository(Tarea::class)->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.fechaBaja IS NULL')
            ->andWhere('t.fechaCreacion >= :fechaDesde')
            ->andWhere('t.fechaCreacion <= :fechaHasta')
            ->setParameter('fechaDesde', $fechaDesde)
            ->setParameter('fechaHasta', $fechaHasta);

        if ($idEmpleado) {
            $qb->innerJoin('t.empleados', 'empleado')
                ->andWhere('empleado.id = :idEmpleado')
                ->setParameter('idEmpleado', $idEmpleado);
        }

        $total = (int) $qb->getQuery()->getSingleScalarResult();

        $qbAsignadas = $em->getRepository(Tarea::class)->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.estado', 'e')
            ->where('t.fechaBaja IS NULL')
            ->andWhere('t.fechaCreacion >= :fechaDesde')
            ->andWhere('t.fechaCreacion <= :fechaHasta')
            ->andWhere('e.codigoInterno = :codigoAsignada')
            ->setParameter('fechaDesde', $fechaDesde)
            ->setParameter('fechaHasta', $fechaHasta)
            ->setParameter('codigoAsignada', ConstanteEstadoTarea::ASIGNADA);

        if ($idEmpleado) {
            $qbAsignadas->innerJoin('t.empleados', 'empleadoAsignado')
                ->andWhere('empleadoAsignado.id = :idEmpleado')
                ->setParameter('idEmpleado', $idEmpleado);
        }

        $asignadas = (int) $qbAsignadas->getQuery()->getSingleScalarResult();

        return new JsonResponse([
            'total' => $total,
            'asignadas' => $asignadas,
        ]);
    }

    #[Route('/generar-desde-pedido-problema/{id}', name: 'tarea_generar_desde_pedido_problema', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_ENCARGADO')]
    public function generarDesdePedidoProblema(
        Request $request,
        PedidoProducto $pedidoProducto,
        EntityManagerInterface $em
    ): Response {
        $tarea = new Tarea();
        $tarea->setTitulo(sprintf(
            'Pedido #%d - %s',
            $pedidoProducto->getPedido()->getId(),
            $pedidoProducto->getProductoBandeja()
        ));
        $tarea->setDescripcion($pedidoProducto->getObservacionProblema());

        $form = $this->createForm(TareaType::class, $tarea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fechaProgramada = $form->get('fechaProgramada')->getData();
            $tarea->setFechaProgramada(
                $fechaProgramada ? DateTime::createFromFormat('d/m/Y', $fechaProgramada) : null
            );

            $empleados = $tarea->getEmpleados();
            if (!$empleados->isEmpty()) {
                $tarea->setAsignadoPor($this->getUser());
                $tarea->setAsignadoEn(new DateTime());
                $estado = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::ASIGNADA);
                $this->estadoService->cambiarEstadoTarea($tarea, $estado, 'Tarea creada y asignada');
            } else {
                $estado = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::NUEVA);
                $this->estadoService->cambiarEstadoTarea($tarea, $estado, 'Tarea creada');
            }

            $em->persist($tarea);
            foreach ($empleados as $empleado) {
                $this->iniciarAsignacion($tarea, $empleado, $em);
            }
            $em->flush();

            if (!$empleados->isEmpty()) {
                foreach ($empleados as $empleado) {
                    $this->notificarAsignacion($tarea, $empleado, $em);
                }
                $em->flush();
            }

            return new JsonResponse([
                'result' => 'OK',
                'message' => 'Tarea creada correctamente.',
            ]);
        }

        $status = $form->isSubmitted() ? 422 : 200;

        return $this->render('tarea/_modal_new.html.twig', [
            'form' => $form->createView(),
            'pedidoProducto' => $pedidoProducto,
        ], new Response('', $status));
    }

    private function getIndicadorTareaData()
    {
        $em = $this->doctrine->getManager();

        $total = (int) $em->getRepository(Tarea::class)->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.fechaBaja IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $asignadas = (int) $em->getRepository(Tarea::class)->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.estado', 'e')
            ->where('t.fechaBaja IS NULL')
            ->andWhere('e.codigoInterno = :codigoAsignada')
            ->setParameter('codigoAsignada', ConstanteEstadoTarea::ASIGNADA)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'asignadas' => $asignadas,
        ];
    }

    private function getActividadRecienteData()
    {
        $em = $this->doctrine->getManager();

        $historicos = $em->getRepository(EstadoTareaHistorico::class)->createQueryBuilder('h')
            ->innerJoin('h.tarea', 't')
            ->innerJoin('h.estado', 'e')
            ->where('t.fechaBaja IS NULL')
            ->andWhere('h.fechaBaja IS NULL')
            ->orderBy('h.id', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($historicos as $historico) {
            $tarea = $historico->getTarea();
            $estado = $historico->getEstado();
            $data[] = [
                'id' => $tarea->getId(),
                'actividad' => sprintf('La tarea #%d cambió su estado a %s', $tarea->getId(), $estado->getNombre()),
                'fecha' => $historico->getFechaCreacion(),
                'colorClass' => $estado->getColorIcono() ?: 'primary',
            ];
        }

        return $data;
    }

    private function getTareaRedirectRoute(): string
    {
        return $this->isGranted('ROLE_TAREA_ENCARGADO') ? 'tarea_index' : 'tarea_empleado_index';
    }

    private function notificarAsignacion(Tarea $tarea, Usuario $empleado, EntityManagerInterface $em): void
    {
        $notificacion = new Notificacion();
        $notificacion->setTitulo('Nueva tarea asignada');
        $notificacion->setContenido(sprintf(
            'Se te asignó la tarea: %s',
            substr($tarea->getTitulo(), 0, 100) . (strlen($tarea->getTitulo()) > 100 ? '...' : '')
        ));
        $notificacion->setDestinatarios(['USER_' . $empleado->getId()]);
        $notificacion->setFechaDesde(new DateTime());

        $em->persist($notificacion);
    }

    private function iniciarAsignacion(Tarea $tarea, Usuario $empleado, EntityManagerInterface $em): void
    {
        if ($tarea->getAsignacionActiva($empleado)) {
            return;
        }

        $asignacion = new TareaAsignacion();
        $asignacion->setEmpleado($empleado);
        $asignacion->setFechaInicio(new DateTime());
        $tarea->addAsignacion($asignacion);
        $em->persist($asignacion);
    }

    private function cerrarAsignacion(
        Tarea $tarea,
        Usuario $empleado,
        \DateTimeInterface $fechaFin,
        ?int $porcentaje = null,
        ?string $observacion = null
    ): void {
        $asignacion = $tarea->getAsignacionActiva($empleado);
        if (!$asignacion) {
            return;
        }

        $asignacion->setFechaFin($fechaFin);
        $asignacion->setPorcentajeAvance($porcentaje);
        $asignacion->setObservacionAvance($observacion);
    }

    protected function getAditionalCustomWhereSQL($aliasTable, $request): string
    {
        $route = $request->get('_route');
        if ($route === 'tarea_empleado_mis_tareas_table') {
            return sprintf(
                "EXISTS (SELECT usuarioAsignado FROM App\\Entity\\Usuario usuarioAsignado WHERE usuarioAsignado.id = %d AND usuarioAsignado MEMBER OF %s.empleados) AND %s.estado IN (SELECT et FROM App\\Entity\\EstadoTarea et WHERE et.codigoInterno IN (%d, %d))",
                $this->getUser()->getId(),
                $aliasTable,
                $aliasTable,
                ConstanteEstadoTarea::ASIGNADA,
                ConstanteEstadoTarea::TERMINADA
            );
        }
        if ($route === 'tarea_empleado_disponibles_table') {
            return sprintf(
                "NOT EXISTS (SELECT usuarioAsignado FROM App\\Entity\\Usuario usuarioAsignado WHERE usuarioAsignado.id = %d AND usuarioAsignado MEMBER OF %s.empleados) AND %s.estado IN (SELECT et FROM App\\Entity\\EstadoTarea et WHERE et.codigoInterno IN (%d, %d))",
                $this->getUser()->getId(),
                $aliasTable,
                $aliasTable,
                ConstanteEstadoTarea::NUEVA,
                ConstanteEstadoTarea::ASIGNADA
            );
        }
        return '';
    }

    private function verificarAccesoTarea(Tarea $tarea): void
    {
        if ($this->isGranted('ROLE_TAREA_ENCARGADO')) {
            return;
        }

        $codigo = (int) $tarea->getEstado()->getCodigoInterno();
        $esDisponible = in_array($codigo, [
            ConstanteEstadoTarea::NUEVA,
            ConstanteEstadoTarea::ASIGNADA,
        ], true) && !$tarea->getEmpleados()->contains($this->getUser());
        $esAsignadaAMi = $codigo === ConstanteEstadoTarea::ASIGNADA
            && $tarea->getEmpleados()->contains($this->getUser());

        if (!$esDisponible && !$esAsignadaAMi) {
            throw new AccessDeniedException('No tenés permiso para acceder a esta tarea.');
        }
    }
}
