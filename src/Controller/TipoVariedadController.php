<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoProducto;
use App\Entity\TipoSubProducto;
use App\Entity\TipoVariedad;
use App\Form\TipoVariedadType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/variedad')]
class TipoVariedadController extends BaseController
{
    #[Route('/', name: 'tipovariedad_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('tipo_variedad/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_variedad_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_variedad';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('nombre_sub_producto', 'nombre_sub_producto');
        $rsm->addScalarResult('nombre_producto', 'nombre_producto');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'nombre_sub_producto', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'nombre_producto', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "tipo_variedad/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_variedad_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoVariedad = new TipoVariedad();
        $form = $this->createForm(TipoVariedadType::class, $tipoVariedad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoVariedad);
            $entityManager->flush();

            return $this->redirectToRoute('tipovariedad_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_variedad/new.html.twig', [
            'tipo_variedad' => $tipoVariedad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_variedad_show', methods: ['GET'])]
    public function show(TipoVariedad $tipoVariedad): Response
    {
        return $this->render('tipo_variedad/show.html.twig', [
            'tipo_variedad' => $tipoVariedad,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_variedad_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoVariedad $tipoVariedad, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoVariedadType::class, $tipoVariedad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipovariedad_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_variedad/new.html.twig', [
            'tipo_variedad' => $tipoVariedad,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_variedad_delete', methods: ['POST'])]
    public function delete(Request $request, TipoVariedad $tipoVariedad, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoVariedad->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoVariedad);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipovariedad_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/lista/variedades", name="lista_variedades")
     */
    public function listaVariedadesAction(Request $request) {
        $idSubProducto = $request->request->get('id_entity');

        $repository = $this->getDoctrine()->getRepository(TipoVariedad::class);

        $query = $repository->createQueryBuilder('l')
            ->select("l.id, l.nombre AS denominacion")
            ->where('l.tipoSubProducto = :subProducto')
            ->setParameter('subProducto', $idSubProducto)
            ->orderBy('l.nombre', 'ASC')
            ->getQuery();

        return new JsonResponse($query->getResult());
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_variedad_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoUsuarioHabilitarDeshabilitar($id) {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoVariedad::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo variedad");

        return $this->redirectToRoute('tipovariedad_index');
    }
}
