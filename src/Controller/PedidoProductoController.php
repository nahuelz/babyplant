<?php

namespace App\Controller;

use App\Entity\PedidoProducto;
use App\Repository\PedidoProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Base;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/pedido/producto")
 */
class PedidoProductoController extends BaseController
{
    #[Route('/', name: 'pedidoproducto_index', methods: ['GET'])]
    public function index(PedidoProductoRepository $pedidoProductoRepository): Response
    {
        return $this->render('pedido_producto/index.html.twig', [
            'pedido_productos' => $pedidoProductoRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'pedido_producto_show', methods: ['GET'])]
    public function show(PedidoProducto $pedidoProducto): Response
    {
        return $this->render('pedido_producto/show.html.twig', [
            'pedido_producto' => $pedidoProducto,
        ]);
    }

    #[Route('/{id}', name: 'pedido_producto_delete', methods: ['POST'])]
    public function delete(Request $request, PedidoProducto $pedidoProducto, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pedidoProducto->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pedidoProducto);
            $entityManager->flush();
        }

        return $this->redirectToRoute('pedidoproducto_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/actualizar-observacion', name: 'pedido_producto_actualizar_observacion', methods: ['POST'])]
    public function actualizarObservacion(Request $request, PedidoProducto $pedidoProducto): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $em = $this->doctrine->getManager();
        $data = json_decode($request->getContent(), true);

        if (isset($data['tipo']) && $data['tipo'] === 'observacion') {
            $pedidoProducto->setObservacion($data['valor']);
        } elseif (isset($data['tipo']) && $data['tipo'] === 'observacion_camara') {
            $pedidoProducto->setObservacionCamara($data['valor']);
        }

        $em->persist($pedidoProducto);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
