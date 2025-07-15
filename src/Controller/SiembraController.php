<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\Pedido;
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
 * @Route("/siembra")
 * @IsGranted("ROLE_SIEMBRA")
 */
class SiembraController extends BaseController
{

    /**
     * @Route("/", name="siembra_index", methods={"GET"})
     * @Template("siembra/index.html.twig")
     * @IsGranted("ROLE_SIEMBRA")
     */
    public function index(): array
    {
        $bread = $this->baseBreadcrumbs;
        $bread['Siembra'] = null;

        return array(
            'breadcrumbs' => $bread,
            'page_title' => 'Siembra'
        );
    }

    /**
     *
     * @Route("/index_table/", name="siembra_table", methods={"GET|POST"})
     * @IsGranted("ROLE_SIEMBRA")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_siembra';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('idPedido', 'idPedido');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('colorBandeja', 'colorBandeja');
        $rsm->addScalarResult('fechaSiembraPlanificacion', 'fechaSiembraPlanificacion');
        $rsm->addScalarResult('className', 'className');

        $renderPage = "siembra/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     *
     * @Route("/guardar_y_sembrar/", name="guardar_y_sembrar", methods={"POST"})
     * @IsGranted("ROLE_SIEMBRA")
     */
    public function guardarOrdenSiembra(Request $request){

        $observacion = $request->get('observacion');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $bandejas = $request->get('bandejas');
        $horaSiembra = $request->get('horaSiembra');
        $fechaSiembra = $request->get('fechaSiembra');

        $em = $this->doctrine->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setObservacion($observacion);
        $pedidoProducto->setCantidadBandejasReales($bandejas);
        $pedidoProducto->setFechaSiembraReal(new DateTime($fechaSiembra));
        $pedidoProducto->setHoraSiembra($horaSiembra);
        $pedidoProducto->setFechaEntradaCamara($pedidoProducto->getFechaSiembraReal());
        if ($pedidoProducto->getEstado()->getCodigoInterno() != ConstanteEstadoPedidoProducto::SEMBRADO) {
            $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::SEMBRADO);
            $this->estadoService->cambiarEstadoPedidoProducto($pedidoProducto, $estado, 'SEMBRADO.');
        }
        $em->flush();

        $message = 'Se sembró correctamente el producto con N° de orden: '.$pedidoProducto->getNumeroOrdenCompleto(). ' - Fecha y hora de siembra: '.$pedidoProducto->getHoraSiembra()->format('Y-m-d H:i');
        $result = array(
            'status' => 'OK',
            'message' => $message
        );
        return new JsonResponse($result);
    }

    /**
     *
     * @Route("/guardar/", name="guardar", methods={"POST"})
     * @IsGranted("ROLE_SIEMBRA")
     */
    public function guardarSiembra(Request $request){

        $observacion = $request->get('observacion');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $bandejas = $request->get('bandejas');

        $em = $this->doctrine->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setObservacion($observacion);
        $pedidoProducto->setCantidadBandejasReales($bandejas);
        $pedidoProducto->setCantidadBandejasSinRemito($bandejas);
        $em->flush();

        $message = 'Se guardó correctamente el producto con N° de orden: '.$pedidoProducto->getNumeroOrdenCompleto();
        $result = array(
            'status' => 'OK',
            'message' => $message
        );
        return new JsonResponse($result);
    }

    /**
     * @Route("/{id}/pedido_producto_siembra", name="pedido_producto_siembra", methods={"GET","POST"})
     * @Template("pedidoproducto/show/siembra_show.html.twig")
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
     * @Route("/pedidos-atrasados/", name="siembra_pedidos_atrasados", methods={"GET","POST"})
     */
    public function pedidosAtrasados(): JsonResponse
    {
        /* @var $pedidoProducto PedidoProducto */
        $pedidosProductos = $this->getDoctrine()->getRepository(PedidoProducto::class)->getPedidosAtrasados(ConstanteEstadoPedidoProducto::PLANIFICADO);
        $html = $this->renderView('planificacion/pedidos_atrasados.html.twig', array('pedidosProductos' => $pedidosProductos));

        $result = array(
            'html' => $html,
            'cantidad' => sizeof($pedidosProductos),
        );

        return new JsonResponse($result);
    }
}