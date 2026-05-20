<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Proveedor;
use App\Form\ProveedorType;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/proveedor")
 * @IsGranted("ROLE_PROVEEDOR_CRUD")
 */
class ProveedorController extends BaseController
{
    #[Route('/', name: 'proveedor_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('proveedor/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="proveedor_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_proveedor';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('cuit', 'cuit');
        $rsm->addScalarResult('email', 'email');
        $rsm->addScalarResult('telefono', 'telefono');
        $rsm->addScalarResult('direccion', 'direccion');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'cuit', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'email', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'telefono', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'direccion', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "proveedor/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'proveedor_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $proveedor = new Proveedor();
        $form = $this->createForm(ProveedorType::class, $proveedor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($proveedor);
            $entityManager->flush();

            return $this->redirectToRoute('proveedor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('proveedor/new.html.twig', [
            'proveedor' => $proveedor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'proveedor_show', methods: ['GET'])]
    public function show(Proveedor $proveedor): Response
    {
        return $this->render('proveedor/show.html.twig', [
            'proveedor' => $proveedor,
        ]);
    }

    #[Route('/{id}/edit', name: 'proveedor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Proveedor $proveedor): Response
    {
        $form = $this->createForm(ProveedorType::class, $proveedor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->flush();

            return $this->redirectToRoute('proveedor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('proveedor/edit.html.twig', [
            'proveedor' => $proveedor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'proveedor_delete', methods: ['POST'])]
    public function delete(Request $request, Proveedor $proveedor): Response
    {
        if ($this->isCsrfTokenValid('delete'.$proveedor->getId(), $request->request->get('_token'))) {
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($proveedor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('proveedor_index', [], Response::HTTP_SEE_OTHER);
    }
}
