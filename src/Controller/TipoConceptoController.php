<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoConcepto;
use App\Form\TipoConceptoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/concepto")
 * @IsGranted("ROLE_PRODUCTO_CRUD")
 */
class TipoConceptoController extends BaseController
{
    #[Route('/', name: 'tipoconcepto_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_concepto/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_concepto_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_concepto';

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

        $renderPage = "tipo_concepto/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_concepto_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoProducto = new TipoConcepto();
        $form = $this->createForm(TipoConceptoType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoProducto);
            $entityManager->flush();

            return $this->redirectToRoute('tipoconcepto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_concepto/new.html.twig', [
            'tipo_concepto' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_concepto_show', methods: ['GET'])]
    public function show(TipoConcepto $tipoProducto): Response
    {
        return $this->render('tipo_concepto/show.html.twig', [
            'tipo_concepto' => $tipoProducto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_concepto_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoConcepto $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoConceptoType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipoconcepto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_concepto/new.html.twig', [
            'tipo_concepto' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_concepto_delete', methods: ['POST'])]
    public function delete(Request $request, TipoConcepto $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipoconcepto_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_concepto_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoConcepto::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo concepto");

        return $this->redirectToRoute('tipoconcepto_index');
    }
}



