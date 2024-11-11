<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoOrigenSemilla;
use App\Entity\TipoProducto;
use App\Form\TipoOrigenSemillaType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/origen/semilla')]
class TipoOrigenSemillaController extends BaseController
{
    #[Route('/', name: 'tipoorigensemilla_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_origen_semilla/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_origen_semilla_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_origen_semilla';

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

        $renderPage = "tipo_origen_semilla/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_origen_semilla_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoOrigenSemilla = new TipoOrigenSemilla();
        $form = $this->createForm(TipoOrigenSemillaType::class, $tipoOrigenSemilla);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoOrigenSemilla);
            $entityManager->flush();

            return $this->redirectToRoute('tipoorigensemilla_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_origen_semilla/new.html.twig', [
            'tipo_origen_semilla' => $tipoOrigenSemilla,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_origen_semilla_show', methods: ['GET'])]
    public function show(TipoOrigenSemilla $tipoOrigenSemilla): Response
    {
        return $this->render('tipo_origen_semilla/show.html.twig', [
            'tipo_origen_semilla' => $tipoOrigenSemilla,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_origen_semilla_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoOrigenSemilla $tipoOrigenSemilla, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoOrigenSemillaType::class, $tipoOrigenSemilla);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipoorigensemilla_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_origen_semilla/new.html.twig', [
            'tipo_origen_semilla' => $tipoOrigenSemilla,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_origen_semilla_delete', methods: ['POST'])]
    public function delete(Request $request, TipoOrigenSemilla $tipoOrigenSemilla, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoOrigenSemilla->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoOrigenSemilla);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipoorigensemilla_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_origen_semilla_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoOrigenSemilla::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo origen semilla");

        return $this->redirectToRoute('tipoorigensemilla_index');
    }
}
