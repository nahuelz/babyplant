<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoUsuario;
use App\Form\TipoUsuarioType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/usuario")
 * @IsGranted("ROLE_TIPO_USUARIO")
 */
class TipoUsuarioController extends BaseController
{
    #[Route('/', name: 'tipousuario_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_usuario/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_usuario_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_usuario';

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

        $renderPage = "tipo_usuario/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_usuario_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoUsuario = new TipoUsuario();
        $form = $this->createForm(TipoUsuarioType::class, $tipoUsuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoUsuario);
            $entityManager->flush();

            return $this->redirectToRoute('tipousuario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_usuario/new.html.twig', [
            'tipo_usuario' => $tipoUsuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_usuario_show', methods: ['GET'])]
    public function show(TipoUsuario $tipoUsuario): Response
    {
        return $this->render('tipo_usuario/show.html.twig', [
            'tipo_usuario' => $tipoUsuario,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_usuario_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoUsuario $tipoUsuario, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoUsuarioType::class, $tipoUsuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipousuario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_usuario/new.html.twig', [
            'tipo_usuario' => $tipoUsuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_usuario_delete', methods: ['POST'])]
    public function delete(Request $request, TipoUsuario $tipoUsuario, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoUsuario->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoUsuario);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipousuario_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_usuario_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoUsuario::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo usuario");

        return $this->redirectToRoute('tipousuario_index');
    }
}
