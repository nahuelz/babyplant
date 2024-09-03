<?php

namespace App\Controller;

use App\Entity\TipoVariedad;
use App\Form\TipoVariedadType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/variedad')]
class TipoVariedadController extends AbstractController
{
    #[Route('/', name: 'app_tipo_variedad_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tipoVariedads = $entityManager
            ->getRepository(TipoVariedad::class)
            ->findAll();

        return $this->render('tipo_variedad/index.html.twig', [
            'tipo_variedads' => $tipoVariedads,
        ]);
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

            return $this->redirectToRoute('app_tipo_variedad_index', [], Response::HTTP_SEE_OTHER);
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

            return $this->redirectToRoute('app_tipo_variedad_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_variedad/edit.html.twig', [
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

        return $this->redirectToRoute('app_tipo_variedad_index', [], Response::HTTP_SEE_OTHER);
    }
}
