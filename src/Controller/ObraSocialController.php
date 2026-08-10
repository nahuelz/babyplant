<?php

namespace App\Controller;

use App\Entity\ObraSocial;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Form\ObraSocialType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/obra_social")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class ObraSocialController extends BaseController
{
    #[Route('/', name: 'obrasocial_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('obra_social/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * @Route("/index_table/", name="obra_social_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_obra_social';

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

        $renderPage = "obra_social/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_obra_social_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $obraSocial = new ObraSocial();
        $form = $this->createForm(ObraSocialType::class, $obraSocial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($obraSocial);
            $entityManager->flush();

            return $this->redirectToRoute('obrasocial_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('obra_social/new.html.twig', [
            'obra_social' => $obraSocial,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_obra_social_show', methods: ['GET'])]
    public function show(ObraSocial $obraSocial): Response
    {
        return $this->render('obra_social/show.html.twig', [
            'obra_social' => $obraSocial,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_obra_social_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ObraSocial $obraSocial, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ObraSocialType::class, $obraSocial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('obrasocial_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('obra_social/new.html.twig', [
            'obra_social' => $obraSocial,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_obra_social_delete', methods: ['POST'])]
    public function delete(Request $request, ObraSocial $obraSocial, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$obraSocial->getId(), $request->request->get('_token'))) {
            $entityManager->remove($obraSocial);
            $entityManager->flush();
        }

        return $this->redirectToRoute('obrasocial_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/habilitar_deshabilitar', name: 'app_obra_social_habilitar_deshabilitar', methods: ['POST'])]
    public function obraSocialHabilitarDeshabilitar(Request $request, ObraSocial $obraSocial, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('obra_social_habilitar_deshabilitar_' . $obraSocial->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('obrasocial_index');
        }

        $obraSocial->setHabilitado(!$obraSocial->getHabilitado());
        $message = ($obraSocial->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $entityManager->flush();

        $this->addFlash('success', "Se " . $message . " correctamente la obra social");

        return $this->redirectToRoute('obrasocial_index');
    }
}
