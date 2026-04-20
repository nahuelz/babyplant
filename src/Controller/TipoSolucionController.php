<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoSolucion;
use App\Form\TipoSolucionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/solucion")
 * @IsGranted("ROLE_PRODUCTO_CRUD")
 */
class TipoSolucionController extends BaseController
{
    #[Route('/', name: 'tiposolucion_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_solucion/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_solucion_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_solucion';

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

        $renderPage = "tipo_solucion/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_solucion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoProducto = new TipoSolucion();
        $form = $this->createForm(TipoSolucionType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoProducto);
            $entityManager->flush();

            return $this->redirectToRoute('tiposolucion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_solucion/new.html.twig', [
            'tipo_solucion' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_solucion_show', methods: ['GET'])]
    public function show(TipoSolucion $tipoProducto): Response
    {
        return $this->render('tipo_solucion/show.html.twig', [
            'tipo_solucion' => $tipoProducto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_solucion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoSolucion $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoSolucionType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tiposolucion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_solucion/new.html.twig', [
            'tipo_solucion' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_solucion_delete', methods: ['POST'])]
    public function delete(Request $request, TipoSolucion $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tiposolucion_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_solucion_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoSolucion::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo solucion");

        return $this->redirectToRoute('tiposolucion_index');
    }

    /**
     * @Route("/api/todos-habilitados", name="tipo_solucion_todos_habilitados", methods={"GET"})
     */
    public function obtenerTodosHabilitados(): Response
    {
        $em = $this->doctrine->getManager();
        $tiposSolucion = $em->getRepository(TipoSolucion::class)->findBy(
            ['habilitado' => true],
            ['nombre' => 'ASC']
        );

        $data = [];
        foreach ($tiposSolucion as $tipo) {
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



