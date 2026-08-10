<?php

namespace App\Controller;

use App\Entity\TipoModalidadPago;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Form\TipoModalidadPagoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo_modalidad_pago")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class TipoModalidadPagoController extends BaseController
{
    #[Route('/', name: 'tipomodalidadpago_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_modalidad_pago/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * @Route("/index_table/", name="tipo_modalidad_pago_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_modalidad_pago';

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

        $renderPage = "tipo_modalidad_pago/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_modalidad_pago_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoModalidadPago = new TipoModalidadPago();
        $form = $this->createForm(TipoModalidadPagoType::class, $tipoModalidadPago);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoModalidadPago);
            $entityManager->flush();

            return $this->redirectToRoute('tipomodalidadpago_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_modalidad_pago/new.html.twig', [
            'tipo_modalidad_pago' => $tipoModalidadPago,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_modalidad_pago_show', methods: ['GET'])]
    public function show(TipoModalidadPago $tipoModalidadPago): Response
    {
        return $this->render('tipo_modalidad_pago/show.html.twig', [
            'tipo_modalidad_pago' => $tipoModalidadPago,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_modalidad_pago_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoModalidadPago $tipoModalidadPago, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoModalidadPagoType::class, $tipoModalidadPago);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipomodalidadpago_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_modalidad_pago/new.html.twig', [
            'tipo_modalidad_pago' => $tipoModalidadPago,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_modalidad_pago_delete', methods: ['POST'])]
    public function delete(Request $request, TipoModalidadPago $tipoModalidadPago, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoModalidadPago->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoModalidadPago);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipomodalidadpago_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/habilitar_deshabilitar', name: 'app_tipo_modalidad_pago_habilitar_deshabilitar', methods: ['POST'])]
    public function tipoModalidadPagoHabilitarDeshabilitar(Request $request, TipoModalidadPago $tipoModalidadPago, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('tipo_modalidad_pago_habilitar_deshabilitar_' . $tipoModalidadPago->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('tipomodalidadpago_index');
        }

        $tipoModalidadPago->setHabilitado(!$tipoModalidadPago->getHabilitado());
        $message = ($tipoModalidadPago->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $entityManager->flush();

        $this->addFlash('success', "Se " . $message . " correctamente la modalidad de pago");

        return $this->redirectToRoute('tipomodalidadpago_index');
    }
}
