<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\TipoBandeja;
use App\Entity\Usuario;
use App\Form\TipoBandejaType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/bandeja')]
class TipoBandejaController extends BaseController
{
    #[Route('/', name: 'tipobandeja_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {

        return $this->render('tipo_bandeja/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * Tabla para usuario.
     *
     * @Route("/index_table/", name="bandeja_table", methods={"GET|POST"})
     *
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_bandeja';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('estandar', 'estandar');
        $rsm->addScalarResult('habilitado', 'habilitado');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombre', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'estandar', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'habilitado', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "tipo_bandeja/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }


    #[Route('/new', name: 'app_tipo_bandeja_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoBandeja = new TipoBandeja();
        $form = $this->createForm(TipoBandejaType::class, $tipoBandeja);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoBandeja);
            $entityManager->flush();

            return $this->redirectToRoute('tipobandeja_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_bandeja/new.html.twig', [
            'tipo_bandeja' => $tipoBandeja,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_bandeja_show', methods: ['GET'])]
    public function show(TipoBandeja $tipoBandeja): Response
    {
        return $this->render('tipo_bandeja/show.html.twig', [
            'tipo_bandeja' => $tipoBandeja,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_bandeja_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoBandeja $tipoBandeja, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoBandejaType::class, $tipoBandeja);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('tipobandeja_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_bandeja/new.html.twig', [
            'tipo_bandeja' => $tipoBandeja,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_bandeja_delete', methods: ['POST'])]
    public function delete(Request $request, TipoBandeja $tipoBandeja, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoBandeja->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoBandeja);
            $entityManager->flush();
        }

        return $this->redirectToRoute('tipobandeja_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/habilitar_deshabilitar", name="app_tipo_bandeja_habilitar_deshabilitar", methods={"GET"})
     */
    public function tipoBandejaHabilitarDeshabilitar($id) {
        $em = $this->getDoctrine()->getManager();
        $bandeja = $em->getRepository(TipoBandeja::class)->findOneBy(array('id' => $id));
        $bandeja->setHabilitado(!$bandeja->getHabilitado());
        $message = ($bandeja->getHabilitado()) ? 'habilitó' : 'deshabilitó';
        $em->flush();
        $this->get('session')->getFlashBag()->add('success', "Se " . $message . " correctamente la bandeja");

        return $this->redirectToRoute('tipobandeja_index');
    }
}
