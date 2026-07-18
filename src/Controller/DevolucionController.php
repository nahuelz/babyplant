<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoDevolucion;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Devolucion;
use App\Entity\EntregaProducto;
use App\Entity\EstadoDevolucion;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Form\DevolucionType;
use App\Service\ReventaService;
use DateTime;
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
            // Establecer estado PENDIENTE antes de persistir
            $estadoPendiente = $em->getRepository(EstadoDevolucion::class)->find(ConstanteEstadoDevolucion::PENDIENTE);
            if ($estadoPendiente) {
                $this->estadoService->cambiarEstadoDevolucion($entity, $estadoPendiente, 'Creación de devolución');
            }

            $em->persist($entity);
            $em->flush();

            // Generar histórico de estado en el pedidoProducto sin cambiar el estado
            $pedidoProducto = $entity->getEntregaProducto()->getPedidoProducto();
            $estadoDevolucion = $em->getRepository(EstadoPedidoProducto::class)->find(ConstanteEstadoPedidoProducto::DEVOLUCION);
            $historico = new EstadoPedidoProductoHistorico();
            $historico->setPedidoProducto($pedidoProducto);
            $historico->setFecha(new DateTime());
            $historico->setEstado($estadoDevolucion);
            $historico->setMotivo('Devolución.');
            $historico->setDevolucion($entity);
            $pedidoProducto->addHistoricoEstado($historico);
            $em->persist($historico);
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
        if ($devolucion->getCantidadRevendida() > 0) {
            $this->addFlash('error', 'La devolución tiene reventas asociadas, no se puede eliminar.');
            return $this->redirectToRoute('devolucion_index');
        }

        $em->remove($devolucion);
        $em->flush();

        $this->addFlash('success', 'La devolución fue eliminada correctamente.');

        return $this->redirectToRoute('devolucion_index');
    }

    /**
     * @Route("/{id}/cancelar", name="devolucion_cancelar", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function cancelar(Devolucion $devolucion, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        if ($devolucion->getEstado() != null && $devolucion->getEstado()->getCodigoInterno() != ConstanteEstadoDevolucion::PENDIENTE) {
            $this->addFlash('error', 'Solo se pueden cancelar devoluciones en estado PENDIENTE.');
            return $this->redirectToRoute('devolucion_index');
        }

        $motivo = $request->request->get('motivo', '');
        $devolucion->setMotivoCancelacion($motivo ?: null);

        $estadoCancelada = $em->getRepository(EstadoDevolucion::class)->find(ConstanteEstadoDevolucion::CANCELADA);
        if ($estadoCancelada) {
            $motivoHistorico = $motivo ? 'Cancelación de devolución: ' . $motivo : 'Cancelación de devolución';
            $this->estadoService->cambiarEstadoDevolucion($devolucion, $estadoCancelada, $motivoHistorico);
            
            // Actualizar bandejas disponibles del pedidoProducto
            $pedidoProducto = $devolucion->getEntregaProducto()->getPedidoProducto();
            $pedidoProducto->setCantidadBandejasDisponibles();
            
            $em->flush();
        }

        $this->addFlash('success', 'La devolución fue cancelada correctamente.');

        return $this->redirectToRoute('devolucion_index');
    }

    /**
     * Descarta las bandejas disponibles de la devolución.
     *
     * @Route("/{id}/descartar", name="devolucion_descartar", methods={"GET","POST"}, requirements={"id"="\d+"})
     */
    public function descartar(Devolucion $devolucion, ReventaService $reventaService): RedirectResponse
    {
        try {
            $reventaService->descartarDevolucion($devolucion);
            $this->addFlash('success', 'La devolución fue descartada correctamente.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('devolucion_index');
    }

    /**
     * @Route("/{id}/historico_estados", name="devolucion_historico_estado", methods={"POST"})
     * @Template("devolucion/historico_estados.html.twig")
     */
    public function showHistoricoEstadoAction($id): array
    {
        $em = $this->doctrine->getManager();
        $devolucion = $em->getRepository(Devolucion::class)->find($id);

        return array(
            'entity' => $devolucion,
            'historicoEstados' => $devolucion->getHistoricoEstados(),
            'page_title' => 'Histórico de estados'
        );
    }

    /**
     * Devuelve los productos (EntregaProducto) de un cliente para filtrar en el formulario.
     *
     * @Route("/lista/productos", name="devolucion_lista_productos", methods={"GET","POST"})
     */
    public function listaProductosAction(Request $request): JsonResponse
    {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->doctrine->getRepository(EntregaProducto::class);

        $resultados = $repository->createQueryBuilder('ep')
            ->select("ep.id, ep.cantidadBandejas, ep.precioUnitario, concat('ENTREGA N° ', e.id, ' - ', pp.numeroOrden, ' ', tp.nombre, ' ', v.nombre, ' (x', tb.nombre, ')') as descripcion, e.id as numeroEntrega, pp.numeroOrden, tm.nombre as mesada, p.id as numeroPedido")
            ->leftJoin('ep.entrega', 'e')
            ->leftJoin('ep.pedidoProducto', 'pp')
            ->leftJoin('pp.pedido', 'p')
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:TipoBandeja', 'tb', Join::WITH, 'pp.tipoBandeja = tb')
            ->leftJoin('pp.mesadaUno', 'm1')
            ->leftJoin('m1.tipoMesada', 'tm')
            ->where('e.clienteEntrega = :cliente')
            ->setParameter('cliente', $idCliente)
            ->orderBy('ep.id', 'DESC')
            ->groupBy('ep.id')
            ->getQuery()
            ->getResult();

        $datosFormateados = array_map(function ($row) use ($em) {
            $entregaProducto = $em->getRepository(EntregaProducto::class)->find($row['id']);
            return [
                'id' => $row['id'],
                'denominacion' => $row['descripcion'],
                'numeroEntrega' => $row['numeroEntrega'],
                'numeroOrden' => $row['numeroOrden'],
                'bandejasEntregadas' => $row['cantidadBandejas'],
                'bandejasDisponibles' => $entregaProducto ? $entregaProducto->getCantidadDisponibleParaDevolucion() : $row['cantidadBandejas'],
                'mesada' => $row['mesada'],
                'numeroPedido' => $row['numeroPedido'],
                'precioUnitario' => $row['precioUnitario'],
            ];
        }, $resultados);

        return new JsonResponse($datosFormateados);
    }
}
