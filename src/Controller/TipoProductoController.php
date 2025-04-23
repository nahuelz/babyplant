<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoProducto;
use App\Entity\TipoUsuario;
use App\Form\TipoProductoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/tipo/producto")
 * @IsGranted("ROLE_PRODUCTO_CRUD")
 */
class TipoProductoController extends BaseController
{
    #[Route('/', name: 'tipoproducto_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_producto/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_producto_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_producto';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('cantidad_dias_camara', 'cantidad_dias_camara');
        $rsm->addScalarResult('ultima_mesada', 'ultima_mesada');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'cantidad_dias_camara', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "tipo_producto/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_producto_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoProducto = new TipoProducto();
        $form = $this->createForm(TipoProductoType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoProducto);
            $entityManager->flush();

            return $this->redirectToRoute('tipoproducto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_producto/new.html.twig', [
            'tipo_producto' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_producto_show', methods: ['GET'])]
    public function show(TipoProducto $tipoProducto): Response
    {
        return $this->render('tipo_producto/show.html.twig', [
            'tipo_producto' => $tipoProducto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_producto_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoProducto $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoProductoType::class, $tipoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipoproducto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_producto/new.html.twig', [
            'tipo_producto' => $tipoProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_producto_delete', methods: ['POST'])]
    public function delete(Request $request, TipoProducto $tipoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipoproducto_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_producto_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoProducto::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo producto");

        return $this->redirectToRoute('tipoproducto_index');
    }
}



