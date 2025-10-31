<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\PedidoProducto;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/planificacion")
 * @IsGranted("ROLE_PLANIFICAR")
 */
class PlanificacionController extends BaseController
{

    /**
     * @Route("/", name="planificacion_index", methods={"GET"})
     * @Template("planificacion/index.html.twig")
     * @IsGranted("ROLE_PLANIFICAR")
     */
    public function index(): array
    {
        $bread = $this->baseBreadcrumbs;
        $bread['Planificacion'] = null;

        return array(
            'breadcrumbs' => $bread,
            'page_title' => 'Planificacion'
        );
    }

    /**
     *
     * @Route("/index_table/", name="planificacion_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PLANIFICAR")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_planificacion';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('idPedido', 'idPedido');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('colorBandeja', 'colorBandeja');
        $rsm->addScalarResult('colorProducto', 'colorProducto');
        $rsm->addScalarResult('fechaSiembraPlanificacion', 'fechaSiembraPlanificacion');
        $rsm->addScalarResult('className', 'className');
        $rsm->addScalarResult('producto', 'producto');
        $rsm->addScalarResult('tipoProducto', 'tipoProducto');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('codigoSobre', 'codigoSobre');
        $rsm->addScalarResult('cliente', 'cliente');

        $renderPage = "planificacion/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="planificacion_show", methods={"GET"})
     * @Template("pedido/show.html.twig")
     * @IsGranted("ROLE_PLANIFICAR")
     */
    public function show($id): Array {
        $em = $this->doctrine->getManager();

        $entity = $em->getRepository("App\Entity\Pedido")->find($id);

        if (!$entity) {
            throw $this->createNotFoundException("No se puede encontrar la entidad Pedido.");
        }

        $breadcrumbs = $this->getShowBaseBreadcrumbs($entity);

        $parametros = array(
            'entity' => $entity,
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Detalle ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersShowAction($entity));
    }

    /**
     *
     * @Route("/cambiar_fecha_planificacion/", name="cambiar_fecha_planificacion", methods={"POST"})
     * @IsGranted("ROLE_PLANIFICAR")
     */
    public function cambiarFechaPlanificacion(Request $request){

        $nuevaFechaSiembraParam = $request->get('nuevaFechaSiembra');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $datetime = new DateTime();
        $nuevaFechaSiembra = $datetime->createFromFormat('Y-m-d', $nuevaFechaSiembraParam);

        $em = $this->doctrine->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $fechaSiembraOriginal = $pedidoProducto->getFechaSiembraPlanificacion();
        $pedidoProducto->setFechaSiembraPlanificacion($nuevaFechaSiembra);
        $em->flush();

        $message = 'Se modifico correctamente la fecha de siembra del producto '.$pedidoProducto->getNombreCompleto().' del dia: '.$fechaSiembraOriginal->format('d/m/Y').' al dia: '.$nuevaFechaSiembra->format('d/m/Y');
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }

    /**
     *
     * @Route("/guardar_orden_siembra/", name="guardar_orden_siembra", methods={"POST"})
     * @IsGranted("ROLE_PLANIFICAR")
     */
    public function guardarOrdenSiembra(Request $request){

        $codSobre = $request->get('codSobre');
        $observacion = $request->get('observacion');
        $idPedidoProducto = $request->get('idPedidoProducto');

        $em = $this->doctrine->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setObservacion($observacion);
        if (($pedidoProducto->getEstado()->getCodigoInterno() != ConstanteEstadoPedidoProducto::PLANIFICADO) && ($codSobre != '')) {
            $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::PLANIFICADO);
            $this->estadoService->cambiarEstadoPedidoProducto($pedidoProducto, $estado, 'PLANIFICADO.');
            $pedidoProducto->setNumeroOrden($this->getDoctrine()->getRepository(PedidoProducto::class)->getSiguienteNumeroOrden($pedidoProducto->getTipoProducto()));
        }
        $pedidoProducto->setCodigoSobre($codSobre);
        $pedidoProducto->setObservacion($observacion);
        $em->flush();

        $message = 'Se guardo correctamente el orden de siembra';
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }

    /**
     * @Route("/{id}/producto_show", name="planificacion_producto_show", methods={"GET","POST"})
     * @Template("pedido_producto/show/planificacion_show.html.twig")
     */
    public function showPedidoProductoAction($id) {

        $em = $this->doctrine->getManager();

        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($id);

        if (!$pedidoProducto) {
            throw $this->createNotFoundException('No se puede encontrar el producto.');
        }

        return array(
            'entity' => $pedidoProducto,
            'page_title' => 'Pedido Producto'
        );
    }
    /**
     * @Route("/pedidos-atrasados/", name="planificacion_pedidos_atrasados", methods={"GET","POST"})
     */
    public function pedidosAtrasados(){
        /* @var $pedidoProducto PedidoProducto */
       $pedidosProductos = $this->getDoctrine()->getRepository(PedidoProducto::class)->getPedidosAtrasados(ConstanteEstadoPedidoProducto::PENDIENTE);
        $html = $this->renderView('planificacion/pedidos_atrasados.html.twig', array('pedidosProductos' => $pedidosProductos));

        $result = array(
            'html' => $html,
            'cantidad' => sizeof($pedidosProductos),
        );

        return new JsonResponse($result);
    }
}