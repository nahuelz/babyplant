<?php

namespace App\Controller;

use App\Entity\TipoUsuario;
use App\Form\TipoUsuarioType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/usuario')]
class TipoUsuarioController extends AbstractController
{
    #[Route('/', name: 'app_tipo_usuario_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tipoUsuarios = $entityManager
            ->getRepository(TipoUsuario::class)
            ->findAll();

        return $this->render('tipo_usuario/index.html.twig', [
            'tipo_usuarios' => $tipoUsuarios,
        ]);
    }

    #[Route('/new', name: 'app_tipo_usuario_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tipoUsuario = new TipoUsuario();
        $form = $this->createForm(TipoUsuarioType::class, $tipoUsuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tipoUsuario);
            $entityManager->flush();

            return $this->redirectToRoute('app_tipo_usuario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_usuario/new.html.twig', [
            'tipo_usuario' => $tipoUsuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_usuario_show', methods: ['GET'])]
    public function show(TipoUsuario $tipoUsuario): Response
    {
        return $this->render('tipo_usuario/show.html.twig', [
            'tipo_usuario' => $tipoUsuario,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tipo_usuario_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TipoUsuario $tipoUsuario, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TipoUsuarioType::class, $tipoUsuario);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tipo_usuario_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_usuario/edit.html.twig', [
            'tipo_usuario' => $tipoUsuario,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_usuario_delete', methods: ['POST'])]
    public function delete(Request $request, TipoUsuario $tipoUsuario, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tipoUsuario->getId(), $request->request->get('_token'))) {
            $entityManager->remove($tipoUsuario);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tipo_usuario_index', [], Response::HTTP_SEE_OTHER);
    }
}
