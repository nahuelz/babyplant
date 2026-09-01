<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoTarea;
use App\Entity\EstadoTarea;
use App\Entity\EstadoTareaHistorico;
use App\Entity\Notificacion;
use App\Entity\Tarea;
use App\Entity\Usuario;
use App\Form\TareaAsignarType;
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
    #[IsGranted('ROLE_TAREA_EMPLEADO')]
    public function indexEmpleado(): Response
    {
        return $this->render('tarea/index_empleado.html.twig', parent::baseIndexAction());
    }

    #[Route('/empleado/mis_tareas_table/', name: 'tarea_empleado_mis_tareas_table', methods: ['GET|POST'])]
    #[IsGranted('ROLE_TAREA_EMPLEADO')]
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
    #[IsGranted('ROLE_TAREA_EMPLEADO')]
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
    #[IsGranted('ROLE_TAREA_EMPLEADO')]
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
            if ($codigo !== ConstanteEstadoTarea::NUEVA || $tarea->getEmpleado() !== null) {
                throw new \RuntimeException('La tarea ya no está disponible.');
            }

            $estadoAsignada = $em->getRepository(EstadoTarea::class)
                ->findOneByCodigoInterno(ConstanteEstadoTarea::ASIGNADA);

            $tarea->setEmpleado($this->getUser());
            $tarea->setAsignadoPor($this->getUser());
            $tarea->setAsignadoEn(new DateTime());

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

    #[Route('/{id}/finalizar', name: 'tarea_finalizar', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_EMPLEADO')]
    public function finalizar(Request $request, Tarea $tarea, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('tarea_finalizar_' . $tarea->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        if ($tarea->getEmpleado() === null || $tarea->getEmpleado()->getId() !== $this->getUser()->getId()) {
            throw new AccessDeniedException('No podés finalizar una tarea que no te fue asignada.');
        }

        $codigo = (int) $tarea->getEstado()->getCodigoInterno();
        if ($codigo !== ConstanteEstadoTarea::ASIGNADA) {
            $this->addFlash('error', 'Solo se pueden finalizar tareas asignadas.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        $estadoTerminada = $em->getRepository(EstadoTarea::class)
            ->findOneByCodigoInterno(ConstanteEstadoTarea::TERMINADA);

        $tarea->setTerminadoEn(new DateTime());

        $this->estadoService->cambiarEstadoTarea($tarea, $estadoTerminada, 'Finalizada por empleado');

        $em->flush();

        $this->addFlash('success', 'Tarea finalizada correctamente.');
        return $this->redirectToRoute($this->getTareaRedirectRoute());
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
            $qb->andWhere('t.empleado = :idEmpleado')
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

            $empleado = $tarea->getEmpleado();
            $asignada = $empleado !== null;

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
            $em->flush();

            if ($asignada) {
                $this->notificarAsignacion($tarea, $empleado, $em);
                $em->flush();
            }

            $this->addFlash('success', 'Tarea creada correctamente.');
            return $this->redirectToRoute($this->getTareaRedirectRoute());
        }

        return $this->renderForm('tarea/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'tarea_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_EMPLEADO')]
    public function show(Tarea $tarea): Response
    {
        $this->verificarAccesoTarea($tarea);

        return $this->render('tarea/show.html.twig', [
            'tarea' => $tarea,
            'historicoEstados' => $tarea->getHistoricoEstados(),
        ]);
    }

    #[Route('/{id}/historico_estados', name: 'tarea_historico_estado', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TAREA_EMPLEADO')]
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
            $form->remove('empleado');
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
            $form->remove('empleado');
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
            'empleado' => $tarea->getEmpleado(),
        ]);
        $form->handleRequest($request);
        $isAjax = $request->isXmlHttpRequest();

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Usuario|null $empleado */
            $empleado = $form->get('empleado')->getData();

            if ($empleado === null) {
                $tarea->setEmpleado(null);
                $tarea->setAsignadoPor(null);
                $tarea->setAsignadoEn(null);

                $estadoNueva = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::NUEVA);
                $this->estadoService->cambiarEstadoTarea($tarea, $estadoNueva, 'Desasignada por encargado');
            } else {
                $estadoAsignada = $em->getRepository(EstadoTarea::class)
                    ->findOneByCodigoInterno(ConstanteEstadoTarea::ASIGNADA);

                $tarea->setEmpleado($empleado);
                $tarea->setAsignadoPor($this->getUser());
                $tarea->setAsignadoEn(new DateTime());

                $motivo = $codigo === ConstanteEstadoTarea::ASIGNADA
                    ? 'Reasignada por encargado'
                    : 'Asignada por encargado';

                $this->estadoService->cambiarEstadoTarea($tarea, $estadoAsignada, $motivo);
                $this->notificarAsignacion($tarea, $empleado, $em);
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
            $qb->andWhere('t.empleado = :idEmpleado')
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
            $qbAsignadas->andWhere('t.empleado = :idEmpleado')
                ->setParameter('idEmpleado', $idEmpleado);
        }

        $asignadas = (int) $qbAsignadas->getQuery()->getSingleScalarResult();

        return new JsonResponse([
            'total' => $total,
            'asignadas' => $asignadas,
        ]);
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
            'Se te asignó la tarea #%d: %s',
            $tarea->getId(),
            substr($tarea->getDescripcion(), 0, 100) . (strlen($tarea->getDescripcion()) > 100 ? '...' : '')
        ));
        $notificacion->setDestinatarios(['ROLE_TAREA_EMPLEADO']);
        $notificacion->setFechaDesde(new DateTime());

        $em->persist($notificacion);
    }

    protected function getAditionalCustomWhereSQL($aliasTable, $request): string
    {
        $route = $request->get('_route');
        if ($route === 'tarea_empleado_mis_tareas_table') {
            return sprintf(
                "%s.empleado IS NOT NULL AND IDENTITY(%s.empleado) = %d AND %s.estado IN (SELECT et FROM App\Entity\EstadoTarea et WHERE et.codigoInterno IN (%d, %d))",
                $aliasTable,
                $aliasTable,
                $this->getUser()->getId(),
                $aliasTable,
                ConstanteEstadoTarea::ASIGNADA,
                ConstanteEstadoTarea::TERMINADA
            );
        }
        if ($route === 'tarea_empleado_disponibles_table') {
            return sprintf(
                "%s.empleado IS NULL AND %s.estado IN (SELECT et FROM App\Entity\EstadoTarea et WHERE et.codigoInterno = %d)",
                $aliasTable,
                $aliasTable,
                ConstanteEstadoTarea::NUEVA
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
        $esNuevaDisponible = $codigo === ConstanteEstadoTarea::NUEVA && $tarea->getEmpleado() === null;
        $esAsignadaAMi = $codigo === ConstanteEstadoTarea::ASIGNADA
            && $tarea->getEmpleado() !== null
            && $tarea->getEmpleado()->getId() === $this->getUser()->getId();

        if (!$esNuevaDisponible && !$esAsignadaAMi) {
            throw new AccessDeniedException('No tenés permiso para acceder a esta tarea.');
        }
    }
}
