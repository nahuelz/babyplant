<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Empleado;
use App\Entity\Vacaciones;
use App\Form\EmpleadoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/empleado")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class EmpleadoController extends BaseController
{
    #[Route('/', name: 'empleado_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('empleado/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * @Route("/index_table/", name="empleado_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_empleado';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombreCompleto', 'nombreCompleto');
        $rsm->addScalarResult('dni', 'dni');
        $rsm->addScalarResult('categoria', 'categoria');
        $rsm->addScalarResult('modalidadPago', 'modalidadPago');
        $rsm->addScalarResult('fechaIngreso', 'fechaIngreso');
        $rsm->addScalarResult('activo', 'activo');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombreCompleto', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'dni', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'categoria', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'modalidadPago', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'fechaIngreso', 'type' => 'date', 'searchable' => true, 'sortable' => true],
            ['field' => 'activo', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "empleado/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_empleado_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $empleado = new Empleado();
        $form = $this->createForm(EmpleadoType::class, $empleado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($empleado);
            $entityManager->flush();

            return $this->redirectToRoute('empleado_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('empleado/new.html.twig', [
            'empleado' => $empleado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_empleado_show', methods: ['GET'])]
    public function show(Empleado $empleado): Response
    {
        return $this->render('empleado/show.html.twig', [
            'empleado' => $empleado,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_empleado_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmpleadoType::class, $empleado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('empleado_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('empleado/new.html.twig', [
            'empleado' => $empleado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_empleado_delete', methods: ['POST'])]
    public function delete(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$empleado->getId(), $request->request->get('_token'))) {
            $entityManager->remove($empleado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('empleado_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/activar_desactivar', name: 'app_empleado_activar_desactivar', methods: ['POST'])]
    public function empleadoActivarDesactivar(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('empleado_activar_desactivar_' . $empleado->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('empleado_index');
        }

        $empleado->setActivo(!$empleado->isActivo());
        $message = ($empleado->isActivo()) ? 'activó' : 'desactivó';
        $entityManager->flush();

        $this->addFlash('success', "Se " . $message . " correctamente al empleado");

        return $this->redirectToRoute('empleado_index');
    }

    /**
     * Carga/actualiza los días de vacaciones correspondientes al empleado para un año.
     *
     * @Route("/{id}/vacaciones", name="app_empleado_vacaciones", methods={"POST"})
     */
    public function guardarVacaciones(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        $anio = (int) $request->request->get('anio');
        $diasCorrespondientes = (int) $request->request->get('diasCorrespondientes');
        $diasTomados = (int) $request->request->get('diasTomados', 0);

        $vacaciones = $entityManager->getRepository(Vacaciones::class)->findOneBy([
            'empleado' => $empleado,
            'anio' => $anio,
        ]);

        if (!$vacaciones) {
            $vacaciones = new Vacaciones();
            $vacaciones->setEmpleado($empleado);
            $vacaciones->setAnio($anio);
        }

        $vacaciones->setDiasCorrespondientes($diasCorrespondientes);
        $vacaciones->setDiasTomados($diasTomados);

        $entityManager->persist($vacaciones);
        $entityManager->flush();

        $this->addFlash('success', 'Vacaciones actualizadas correctamente');

        return $this->redirectToRoute('app_empleado_show', ['id' => $empleado->getId()]);
    }
}
