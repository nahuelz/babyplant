<?php

namespace App\Controller;

use App\Entity\PedidoProducto;
use App\Form\PedidoProducto1Type;
use App\Repository\PedidoProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pedido/producto')]
class PedidoProductoController extends AbstractController
{
    #[Route('/', name: 'app_pedido_producto_index', methods: ['GET'])]
    public function index(PedidoProductoRepository $pedidoProductoRepository): Response
    {
        return $this->render('pedido_producto/index.html.twig', [
            'pedido_productos' => $pedidoProductoRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_pedido_producto_show', methods: ['GET'])]
    public function show(PedidoProducto $pedidoProducto): Response
    {
        return $this->render('pedido_producto/show.html.twig', [
            'pedido_producto' => $pedidoProducto,
        ]);
    }

    #[Route('/{id}', name: 'app_pedido_producto_delete', methods: ['POST'])]
    public function delete(Request $request, PedidoProducto $pedidoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pedidoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pedidoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pedido_producto_index', [], Response::HTTP_SEE_OTHER);
    }
}
