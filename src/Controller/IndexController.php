<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\PedidoProducto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
/**
 * @IsGranted("ROLE_USER")
 */
class IndexController extends BaseController {

    /**
     * @Route("/", name="index_index", methods={"GET"})
     */
    public function index(Request $request, EntityManagerInterface $entityManager) {
        // Obtener conteo de pedidos con problemas
        $pedidosConProblemas = $entityManager->getRepository(PedidoProducto::class)
            ->createQueryBuilder('pp')
            ->select('COUNT(pp.id)')
            ->where('pp.tieneProblema = :tieneProblema')
            ->andWhere('pp.tieneSolucion = :tieneSolucion')
            ->setParameter('tieneProblema', true)
            ->setParameter('tieneSolucion', false)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('index.html.twig', [
            'controller_name' => 'IndexController',
            'pedidos_con_problemas_count' => $pedidosConProblemas,
        ]);
    }

}