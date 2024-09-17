<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoProducto;
use App\Entity\TipoSubProducto;
use App\Form\TipoSubProductoType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/sub/producto')]
class TipoSubProductoController  extends BaseController
{
    #[Route('/', name: 'tiposubproducto_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_sub_producto/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_sub_producto_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_sub_producto';

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

        $renderPage = "tipo_sub_producto/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_sub_producto_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoSubProducto = new TipoSubProducto();
        $form = $this->createForm(TipoSubProductoType::class, $tipoSubProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoSubProducto);
            $entityManager->flush();

            return $this->redirectToRoute('tiposubproducto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_sub_producto/new.html.twig', [
            'tipo_sub_producto' => $tipoSubProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_sub_producto_show', methods: ['GET'])]
    public function show(TipoSubProducto $tipoSubProducto): Response
    {
        return $this->render('tipo_sub_producto/show.html.twig', [
            'tipo_sub_producto' => $tipoSubProducto,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_sub_producto_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoSubProducto $tipoSubProducto, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoSubProductoType::class, $tipoSubProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tiposubproducto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_sub_producto/new.html.twig', [
            'tipo_sub_producto' => $tipoSubProducto,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_sub_producto_delete', methods: ['POST'])]
    public function delete(Request $request, TipoSubProducto $tipoSubProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoSubProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoSubProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tiposubproducto_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/lista/subproductos", name="lista_subproductos")
     */
    public function listaSubProductosAction(Request $request) {
        $id_producto = $request->request->get('id_entity');

        $repository = $this->getDoctrine()->getRepository(TipoSubProducto::class);

        $query = $repository->createQueryBuilder('l')
            ->select("l.id, l.nombre AS denominacion")
            ->where('l.tipoProducto = :producto')
            ->andWhere('l.habilitado = 1')
            ->setParameter('producto', $id_producto)
            ->orderBy('l.nombre', 'ASC')
            ->getQuery();
        return new JsonResponse($query->getResult());
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_sub_producto_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->getDoctrine()->getManager();
        $tipo = $em->getRepository(TipoSubProducto::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo sub producto");

        return $this->redirectToRoute('tiposubproducto_index');
    }
}
