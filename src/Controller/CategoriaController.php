<?php

namespace App\Controller;

use App\Entity\Categoria;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Form\CategoriaType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/categoria")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class CategoriaController extends BaseController
{
    #[Route('/', name: 'categoria_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('categoria/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * @Route("/index_table/", name="categoria_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_categoria';

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

        $renderPage = "categoria/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_categoria_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categoria = new Categoria();
        $form = $this->createForm(CategoriaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categoria);
            $entityManager->flush();

            return $this->redirectToRoute('categoria_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('categoria/new.html.twig', [
            'categoria' => $categoria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categoria_show', methods: ['GET'])]
    public function show(Categoria $categoria): Response
    {
        return $this->render('categoria/show.html.twig', [
            'categoria' => $categoria,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categoria_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Categoria $categoria, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoriaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('categoria_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('categoria/new.html.twig', [
            'categoria' => $categoria,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categoria_delete', methods: ['POST'])]
    public function delete(Request $request, Categoria $categoria, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categoria->getId(), $request->request->get('_token'))) {
            $entityManager->remove($categoria);
            $entityManager->flush();
        }

        return $this->redirectToRoute('categoria_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/habilitar_deshabilitar', name: 'app_categoria_habilitar_deshabilitar', methods: ['POST'])]
    public function categoriaHabilitarDeshabilitar(Request $request, Categoria $categoria, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('categoria_habilitar_deshabilitar_' . $categoria->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('categoria_index');
        }

        $categoria->setHabilitado(!$categoria->getHabilitado());
        $message = ($categoria->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $entityManager->flush();

        $this->addFlash('success', "Se " . $message . " correctamente la categoría");

        return $this->redirectToRoute('categoria_index');
    }
}
