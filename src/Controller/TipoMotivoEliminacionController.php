<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoMotivoEliminacion;
use App\Form\TipoMotivoEliminacionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/motivo/eliminacion")
 * @IsGranted("ROLE_PROBLEMA")
 */
class TipoMotivoEliminacionController extends BaseController
{
    #[Route('/', name: 'tipomotivoeliminacion_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_motivo_eliminacion/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_motivo_eliminacion_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_motivo_eliminacion';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "tipo_motivo_eliminacion/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_motivo_eliminacion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoProducto = new TipoMotivoEliminacion();
        $form = $this->createForm(TipoMotivoEliminacionType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoProducto);
            $entityManager->flush();

            return $this->redirectToRoute('tipomotivoeliminacion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_motivo_eliminacion/new.html.twig', [
            'tipo_motivo_eliminacion' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_motivo_eliminacion_show', methods: ['GET'])]
    public function show(TipoMotivoEliminacion $tipoProducto): Response
    {
        return $this->render('tipo_motivo_eliminacion/show.html.twig', [
            'tipo_motivo_eliminacion' => $tipoProducto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_motivo_eliminacion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoMotivoEliminacion $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoMotivoEliminacionType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipomotivoeliminacion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_motivo_eliminacion/new.html.twig', [
            'tipo_motivo_eliminacion' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_motivo_eliminacion_delete', methods: ['POST'])]
    public function delete(Request $request, TipoMotivoEliminacion $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipomotivoeliminacion_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_motivo_eliminacion_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoMotivoEliminacion::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo motivo eliminacion");

        return $this->redirectToRoute('tipomotivoeliminacion_index');
    }

    /**
     * @Route("/api/todos-habilitados", name="tipo_motivo_eliminacion_todos_habilitados", methods={"GET"})
     */
    public function obtenerTodosHabilitados(): Response
    {
        $em = $this->doctrine->getManager();
        $tiposRevision = $em->getRepository(TipoMotivoEliminacion::class)->findBy(
            ['habilitado' => true],
            ['nombre' => 'ASC']
        );

        $data = [];
        foreach ($tiposRevision as $tipo) {
            $data[] = [
                'id' => $tipo->getId(),
                'nombre' => $tipo->getNombre()
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }
}



