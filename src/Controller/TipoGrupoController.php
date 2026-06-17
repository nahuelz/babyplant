<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoGrupo;
use App\Form\TipoGrupoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/grupo")
 * @IsGranted("ROLE_PRODUCTO_CRUD")
 */
class TipoGrupoController extends BaseController
{
    #[Route('/', name: 'tipogrupo_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_grupo/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_grupo_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_grupo';

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

        $renderPage = "tipo_grupo/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_grupo_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoProducto = new TipoGrupo();
        $form = $this->createForm(TipoGrupoType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoProducto);
            $entityManager->flush();

            return $this->redirectToRoute('tipogrupo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_grupo/new.html.twig', [
            'tipo_grupo' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_grupo_show', methods: ['GET'])]
    public function show(TipoGrupo $tipoProducto): Response
    {
        return $this->render('tipo_grupo/show.html.twig', [
            'tipo_grupo' => $tipoProducto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_grupo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoGrupo $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoGrupoType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipogrupo_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_grupo/new.html.twig', [
            'tipo_grupo' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_grupo_delete', methods: ['POST'])]
    public function delete(Request $request, TipoGrupo $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipogrupo_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_grupo_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoGrupo::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo grupo");

        return $this->redirectToRoute('tipogrupo_index');
    }
}



