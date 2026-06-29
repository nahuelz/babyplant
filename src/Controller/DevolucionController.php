<?php

namespace App\Controller;

use App\Entity\Devolucion;
use App\Entity\PedidoProducto;
use App\Form\DevolucionType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/devolucion")
 * @IsGranted("ROLE_DEVOLUCION")
 */
class DevolucionController extends BaseController {

    /**
     * @Route("/", name="devolucion_index", methods={"GET"})
     * @Template("devolucion/index.html.twig")
     */
    public function index(): array
    {
        $em = $this->doctrine->getManager();

        $devoluciones = $em->getRepository(Devolucion::class)
            ->findBy([], ['fechaDevolucion' => 'DESC', 'id' => 'DESC']);

        return array(
            'devoluciones' => $devoluciones,
            'clienteSelect' => $this->getSelectService()->getClienteFilter(),
            'page_title' => 'Devoluciones'
        );
    }

    /**
     * @Route("/new", name="devolucion_new", methods={"GET","POST"})
     * @Template("devolucion/new.html.twig")
     */
    public function new(Request $request, EntityManagerInterface $em): array|RedirectResponse
    {
        $entity = new Devolucion();

        $form = $this->createForm(DevolucionType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();

            $this->addFlash('success', 'La devolución fue registrada correctamente.');

            return $this->redirectToRoute('devolucion_index');
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
            'page_title' => 'Agregar Devolución'
        );
    }

    /**
     * @Route("/{id}", name="devolucion_show", methods={"GET"}, requirements={"id"="\d+"})
     * @Template("devolucion/show.html.twig")
     */
    public function show(Devolucion $devolucion): array
    {
        return array(
            'entity' => $devolucion,
            'page_title' => 'Detalle Devolución'
        );
    }

    /**
     * @Route("/{id}/edit", name="devolucion_edit", methods={"GET","POST"}, requirements={"id"="\d+"})
     * @Template("devolucion/new.html.twig")
     */
    public function edit(Request $request, Devolucion $devolucion, EntityManagerInterface $em): array|RedirectResponse
    {
        $form = $this->createForm(DevolucionType::class, $devolucion);
        $form->get('cliente')->setData($devolucion->getCliente());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'La devolución fue actualizada correctamente.');

            return $this->redirectToRoute('devolucion_index');
        }

        return array(
            'entity' => $devolucion,
            'form' => $form->createView(),
            'page_title' => 'Editar Devolución'
        );
    }

    /**
     * @Route("/{id}/borrar", name="devolucion_delete", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function delete(Devolucion $devolucion, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($devolucion);
        $em->flush();

        $this->addFlash('success', 'La devolución fue eliminada correctamente.');

        return $this->redirectToRoute('devolucion_index');
    }

    /**
     * Devuelve los productos (PedidoProducto) de un cliente para filtrar en el formulario.
     *
     * @Route("/lista/productos", name="devolucion_lista_productos", methods={"GET","POST"})
     */
    public function listaProductosAction(Request $request): JsonResponse
    {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->doctrine->getRepository(PedidoProducto::class);

        $resultados = $repository->createQueryBuilder('pp')
            ->select("pp.id, pp.fechaEntregaPedido, concat('PEDIDO N° ', p.id, ' ORDEN N° ', pp.numeroOrden, ' ', tp.nombre, ' ', v.nombre, ' (x', tb.nombre, ')') as descripcion")
            ->leftJoin('pp.pedido', 'p')
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:TipoBandeja', 'tb', Join::WITH, 'pp.tipoBandeja = tb')
            ->where('p.cliente = :cliente')
            ->setParameter('cliente', $idCliente)
            ->orderBy('pp.id', 'DESC')
            ->groupBy('pp.id')
            ->getQuery()
            ->getResult();

        $datosFormateados = array_map(function ($row) {
            $fechaEntrega = $row['fechaEntregaPedido'] ? ' FECHA ENTREGA: ' . $row['fechaEntregaPedido']->format('d-m-Y') : '';
            return [
                'id' => $row['id'],
                'denominacion' => $row['descripcion'] . $fechaEntrega,
            ];
        }, $resultados);

        return new JsonResponse($datosFormateados);
    }
}
