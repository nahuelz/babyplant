<?php

namespace App\Controller;

use App\Entity\Banco;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Form\BancoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/banco")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class BancoController extends BaseController
{
    #[Route('/', name: 'banco_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('banco/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * @Route("/index_table/", name="banco_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_banco';

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

        $renderPage = "banco/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_banco_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $banco = new Banco();
        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($banco);
            $entityManager->flush();

            return $this->redirectToRoute('banco_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('banco/new.html.twig', [
            'banco' => $banco,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_banco_show', methods: ['GET'])]
    public function show(Banco $banco): Response
    {
        return $this->render('banco/show.html.twig', [
            'banco' => $banco,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_banco_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Banco $banco, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BancoType::class, $banco);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('banco_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('banco/new.html.twig', [
            'banco' => $banco,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_banco_delete', methods: ['POST'])]
    public function delete(Request $request, Banco $banco, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$banco->getId(), $request->request->get('_token'))) {
            $entityManager->remove($banco);
            $entityManager->flush();
        }

        return $this->redirectToRoute('banco_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/habilitar_deshabilitar', name: 'app_banco_habilitar_deshabilitar', methods: ['POST'])]
    public function bancoHabilitarDeshabilitar(Request $request, Banco $banco, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('banco_habilitar_deshabilitar_' . $banco->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('banco_index');
        }

        $banco->setHabilitado(!$banco->getHabilitado());
        $message = ($banco->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $entityManager->flush();

        $this->addFlash('success', "Se " . $message . " correctamente el banco");

        return $this->redirectToRoute('banco_index');
    }
}
