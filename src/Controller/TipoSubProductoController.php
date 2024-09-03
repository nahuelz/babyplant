<?php

namespace App\Controller;

use App\Entity\TipoSubProducto;
use App\Form\TipoSubProductoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tipo/sub/producto')]
class TipoSubProductoController extends AbstractController
{
    #[Route('/', name: 'app_tipo_sub_producto_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tipoSubProductos = $entityManager
            ->getRepository(TipoSubProducto::class)
            ->findAll();

        return $this->render('tipo_sub_producto/index.html.twig', [
            'tipo_sub_productos' => $tipoSubProductos,
        ]);
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

            return $this->redirectToRoute('app_tipo_sub_producto_index', [], Response::HTTP_SEE_OTHER);
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

            return $this->redirectToRoute('app_tipo_sub_producto_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('tipo_sub_producto/edit.html.twig', [
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

        return $this->redirectToRoute('app_tipo_sub_producto_index', [], Response::HTTP_SEE_OTHER);
    }
}
