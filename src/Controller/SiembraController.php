<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\EstadoPedidoHistorico;
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
 */
class SiembraController extends BaseController
{

    /**
     * @Route("/", name="siembra_index", methods={"GET"})
     * @Template("siembra/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
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
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_siembra';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombreCompleto', 'nombreCompleto');
        $rsm->addScalarResult('nombreCorto', 'nombreCorto');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('cantidadTipoBandejabandeja', 'cantidadTipoBandejabandeja');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('tipoBandeja', 'tipoBandeja');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorIcono', 'colorIcono');
        $rsm->addScalarResult('fechaSiembra', 'fechaSiembra');
        $rsm->addScalarResult('descripcion', 'descripcion');
        $rsm->addScalarResult('codigoSobre', 'codigoSobre');
        $rsm->addScalarResult('className', 'className');

        $renderPage = "siembra/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     *
     * @Route("/cambiar_fecha_siembra/", name="cambiar_fecha_siembra", methods={"POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function setChangeData(Request $request){

        $nuevaFechaSiembraParam = $request->get('nuevaFechaSiembra');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $datetime = new DateTime();
        $nuevaFechaSiembra = $datetime->createFromFormat('Y-m-d', $nuevaFechaSiembraParam);

        $em = $this->getDoctrine()->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $fechaSiembraOriginal = $pedidoProducto->getFechaSiembra();
        $pedidoProducto->setFechaSiembra($nuevaFechaSiembra);
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
     * @Route("/guardar_y_sembrar/", name="guardar_y_sembrar", methods={"POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function guardarOrdenSiembra(Request $request){

        $observacion = $request->get('observacion');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $bandejas = $request->get('bandejas');
        $horaSiembra = $request->get('horaSiembra');

        $em = $this->getDoctrine()->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setObservacion($observacion);
        $pedidoProducto->setCantBandejasReales($bandejas);
        $pedidoProducto->setHoraSiembra($horaSiembra);
        if ($pedidoProducto->getEstado()->getCodigoInterno() != ConstanteEstadoPedidoProducto::SEMBRADO) {
            $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::SEMBRADO);
            $motivo = 'Producto sembrado.';
            $this->cambiarEstado($em, $pedidoProducto, $estado, $motivo);
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
     * @IsGranted("ROLE_PEDIDO")
     */
    public function guardarSiembra(Request $request){

        $observacion = $request->get('observacion');
        $idPedidoProducto = $request->get('idPedidoProducto');
        $bandejas = $request->get('bandejas');

        $em = $this->getDoctrine()->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setObservacion($observacion);
        $pedidoProducto->setCantBandejasReales($bandejas);
        $em->flush();

        $message = 'Se guardó correctamente el producto con N° de orden: '.$pedidoProducto->getNumeroOrdenCompleto();
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
    private function cambiarEstado($em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto, $motivo) {

        $pedidoProducto->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico->setMotivo($motivo);
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $em->persist($estadoPedidoProductoHistorico);
    }

    /**
     * @Route("/{id}/pedido_producto_siembra", name="pedido_producto_siembra", methods={"GET","POST"})
     * @Template("siembra/producto_show.html.twig")
     */
    public function showPedidoProductoAction($id) {

        $em = $this->getDoctrine()->getManager();

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
     * @Route("/test_hora", name="test_hora", methods={"GET","POST"})
     */
    public function testHoraAction() {
        $fecha = new DateTime();
        $horaMinutos= '12:30';
        $hora =  intval(substr($horaMinutos,0,2));
        $minutos = intval(substr($horaMinutos,3,2));
        $fecha->setTime($hora,$minutos);
        var_dump($fecha);
        die();
    }
}