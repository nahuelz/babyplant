<?php

namespace App\Controller;

use App\Entity\TipoOrigenSemilla;
use App\Form\TipoOrigenSemillaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/origen/semilla')]
class TipoOrigenSemillaController extends AbstractController
{
    #[Route('/', name: 'app_tipo_origen_semilla_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tipoOrigenSemillas = $entityManager
            ->getRepository(TipoOrigenSemilla::class)
            ->findAll();

        return $this->render('tipo_origen_semilla/index.html.twig', [
            'tipo_origen_semillas' => $tipoOrigenSemillas,
        ]);
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

            return $this->redirectToRoute('app_tipo_origen_semilla_index', [], Response::HTTP_SEE_OTHER);
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

            return $this->redirectToRoute('app_tipo_origen_semilla_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_origen_semilla/edit.html.twig', [
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

        return $this->redirectToRoute('app_tipo_origen_semilla_index', [], Response::HTTP_SEE_OTHER);
    }
}
