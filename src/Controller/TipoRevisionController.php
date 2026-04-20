<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoRevision;
use App\Form\TipoRevisionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/revision")
 * @IsGranted("ROLE_PRODUCTO_CRUD")
 */
class TipoRevisionController extends BaseController
{
    #[Route('/', name: 'tiporevision_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_revision/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_revision_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_revision';

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

        $renderPage = "tipo_revision/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_revision_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoProducto = new TipoRevision();
        $form = $this->createForm(TipoRevisionType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoProducto);
            $entityManager->flush();

            return $this->redirectToRoute('tiporevision_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_revision/new.html.twig', [
            'tipo_revision' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_revision_show', methods: ['GET'])]
    public function show(TipoRevision $tipoProducto): Response
    {
        return $this->render('tipo_revision/show.html.twig', [
            'tipo_revision' => $tipoProducto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_revision_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoRevision $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoRevisionType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tiporevision_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_revision/new.html.twig', [
            'tipo_revision' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_revision_delete', methods: ['POST'])]
    public function delete(Request $request, TipoRevision $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tiporevision_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_revision_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoRevision::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo revision");

        return $this->redirectToRoute('tiporevision_index');
    }

    /**
     * @Route("/api/todos-habilitados", name="tipo_revision_todos_habilitados", methods={"GET"})
     */
    public function obtenerTodosHabilitados(): Response
    {
        $em = $this->doctrine->getManager();
        $tiposRevision = $em->getRepository(TipoRevision::class)->findBy(
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



