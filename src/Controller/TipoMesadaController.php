<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoMesada;
use App\Form\TipoMesadaType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/mesada')]
class TipoMesadaController extends BaseController
{
    #[Route('/', name: 'tipomesada_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('tipo_mesada/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     *
     * @Route("/index_table/", name="tipo_mesada_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_tipo_mesada';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('capacidad', 'capacidad');
        $rsm->addScalarResult('tipoMesada', 'tipoMesada');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "tipo_mesada/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_tipo_mesada_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoMesada = new TipoMesada();
        $form = $this->createForm(TipoMesadaType::class, $tipoMesada);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoMesada);
            $entityManager->flush();

            return $this->redirectToRoute('tipomesada_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_mesada/new.html.twig', [
            'tipo_mesada' => $tipoMesada,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_mesada_show', methods: ['GET'])]
    public function show(TipoMesada $tipoMesada): Response
    {
        return $this->render('tipo_mesada/show.html.twig', [
            'tipo_mesada' => $tipoMesada,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_mesada_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoMesada $tipoMesada, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoMesadaType::class, $tipoMesada);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipomesada_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_mesada/new.html.twig', [
            'tipo_mesada' => $tipoMesada,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_mesada_delete', methods: ['POST'])]
    public function delete(Request $request, TipoMesada $tipoMesada, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoMesada->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoMesada);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipomesada_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/habilitar_deshabilitar', name: 'app_tipo_mesada_habilitar_deshabilitar', methods: ['GET'])]
    public function tipoUsuarioHabilitarDeshabilitar($id): RedirectResponse
    {
        $em = $this->doctrine->getManager();
        $tipo = $em->getRepository(TipoMesada::class)->findOneBy(array('id' => $id));
        $tipo->setHabilitado(!$tipo->getHabilitado());
        $message = ($tipo->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente al tipo mesada");

        return $this->redirectToRoute('tipomesada_index');
    }

    /**
     * @Route("/lista/mesada/producto", name="lista_mesada_producto")
     */
    public function listaMesadaProductoAction(Request $request): JsonResponse
    {
        $id_producto = $request->request->get('id_entity');

        $repository = $this->doctrine->getRepository(TipoMesada::class);

        $query = $repository->createQueryBuilder('m')
            ->select("m.id, CONCAT('N°',' ',m.nombre,' ', tp.nombre, ' Disponible: ', m.disponible) AS denominacion")
            ->leftJoin('m.tipoProducto', 'tp' )
            ->where('m.tipoProducto = :producto')
            ->andWhere("m.capacidad > 0")
            ->andWhere('m.habilitado = 1')
            ->setParameter('producto', $id_producto)
            ->orderBy('m.nombre', 'ASC')
            ->getQuery();

        if (!$id_producto){
            $query = $repository->createQueryBuilder('m')
                ->select("m.id, CONCAT('N°',' ',m.nombre,' ', tp.nombre, ' Disponible: ', m.disponible) AS denominacion")
                ->leftJoin('m.tipoProducto', 'tp')
                ->andWhere('m.habilitado = 1')
                ->orderBy('m.nombre', 'ASC')
                ->getQuery();
        }
        return new JsonResponse($query->getResult());
    }
}
