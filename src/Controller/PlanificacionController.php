<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\PedidoProducto;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/planificacion")
 */
class PlanificacionController extends BaseController
{

    /**
     * @Route("/", name="planificacion_index", methods={"GET"})
     * @Template("planificacion/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
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
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_planificacion';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombreCompleto', 'nombreCompleto');
        $rsm->addScalarResult('nombreCorto', 'nombreCorto');
        $rsm->addScalarResult('cantidadTipoBandejabandeja', 'cantidadTipoBandejabandeja');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('tipoBandeja', 'tipoBandeja');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorIcono', 'colorIcono');
        $rsm->addScalarResult('fechaSiembraReal', 'fechaSiembraReal');
        $rsm->addScalarResult('descripcion', 'descripcion');
        $rsm->addScalarResult('codigoSobre', 'codigoSobre');
        $rsm->addScalarResult('className', 'className');
        $rsm->addScalarResult('tipoProducto', 'tipoProducto');

        $renderPage = "planificacion/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="planificacion_show", methods={"GET"})
     * @Template("pedido/show.html.twig")
     * @IsGranted("ROLE_PEDIDO")
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
     * @IsGranted("ROLE_PEDIDO")
     */
    public function cambiarFechaPlanificacion(Request $request){

        $nuevaFechaSiembraParam = $request->get('nuevaFechaSiembra');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $datetime = new DateTime();
        $nuevaFechaSiembra = $datetime->createFromFormat('Y-m-d', $nuevaFechaSiembraParam);

        $em = $this->doctrine->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $fechaSiembraOriginal = $pedidoProducto->getFechaSiembraReal();
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
     * @IsGranted("ROLE_PEDIDO")
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
            $pedidoProducto->setCodigoSobre($codSobre);
            $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::PLANIFICADO);
            $this->cambiarEstado($em, $pedidoProducto, $estado);
            $pedidoProducto->setNumeroOrden($this->getDoctrine()->getRepository(PedidoProducto::class)->getSiguienteNumeroOrden($pedidoProducto->getTipoProducto()));
        }
        $em->flush();

        $message = 'Se guardo correctamente el orden de siembra';
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }

    /**
     *
     * @param type $em
     * @param PedidoProducto $pedidoProducto
     * @param EstadoPedidoProducto $estadoProducto
     */
    private function cambiarEstado($em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto) {

        $pedidoProducto->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico->setMotivo('Producto planificado.');
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $em->persist($estadoPedidoProductoHistorico);
    }

    /**
     * @Route("/{id}/producto_show", name="planificacion_producto_show", methods={"GET","POST"})
     * @Template("pedidoproducto/show/planificacion_show.html.twig")
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