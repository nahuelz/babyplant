<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoConceptoLiquidacion;
use App\Form\TipoConceptoLiquidacionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipoconcepto/liquidacion")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class TipoConceptoLiquidacionController extends BaseController
{
    #[Route('/', name: 'tipoconceptoliquidacion_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_concepto_liquidacion/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * @Route("/index_table/", name="tipo_concepto_liquidacion_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response
    {
        $entityTable = 'view_tipo_concepto_liquidacion';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('tipo', 'tipo');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'tipo', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "tipo_concepto_liquidacion/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_concepto_liquidacion_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoConceptoLiquidacion = new TipoConceptoLiquidacion();
        $form = $this->createForm(TipoConceptoLiquidacionType::class, $tipoConceptoLiquidacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoConceptoLiquidacion);
            $entityManager->flush();

            return $this->redirectToRoute('tipoconceptoliquidacion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_concepto_liquidacion/new.html.twig', [
            'tipo_concepto_liquidacion' => $tipoConceptoLiquidacion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_concepto_liquidacion_show', methods: ['GET'])]
    public function show(TipoConceptoLiquidacion $tipoConceptoLiquidacion): Response
    {
        return $this->render('tipo_concepto_liquidacion/show.html.twig', [
            'tipo_concepto_liquidacion' => $tipoConceptoLiquidacion,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_concepto_liquidacion_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoConceptoLiquidacion $tipoConceptoLiquidacion, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoConceptoLiquidacionType::class, $tipoConceptoLiquidacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipoconceptoliquidacion_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_concepto_liquidacion/new.html.twig', [
            'tipo_concepto_liquidacion' => $tipoConceptoLiquidacion,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_concepto_liquidacion_delete', methods: ['POST'])]
    public function delete(Request $request, TipoConceptoLiquidacion $tipoConceptoLiquidacion, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tipoConceptoLiquidacion->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoConceptoLiquidacion);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipoconceptoliquidacion_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/habilitar_deshabilitar', name: 'app_tipo_concepto_liquidacion_habilitar_deshabilitar', methods: ['POST'])]
    public function habilitarDeshabilitar(Request $request, TipoConceptoLiquidacion $tipoConceptoLiquidacion, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('tipo_concepto_liquidacion_habilitar_deshabilitar_' . $tipoConceptoLiquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('tipoconceptoliquidacion_index');
        }

        $tipoConceptoLiquidacion->setHabilitado(!$tipoConceptoLiquidacion->getHabilitado());
        $message = $tipoConceptoLiquidacion->getHabilitado() ? 'habilitó' : 'deshabilitó';
        $entityManager->flush();

        $this->addFlash('success', 'Se ' . $message . ' correctamente el concepto');

        return $this->redirectToRoute('tipoconceptoliquidacion_index');
    }
}
