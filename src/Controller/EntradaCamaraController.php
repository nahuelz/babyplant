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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/entrada_camara")
 * @IsGranted("ROLE_ENTRADA_CAMARA")
 */
class EntradaCamaraController extends BaseController
{

    /**
     * @Route("/", name="entradacamara_index", methods={"GET"})
     * @Template("entrada_camara/index.html.twig")
     * @IsGranted("ROLE_ENTRADA_CAMARA")
     */
    public function index(): array
    {
        $bread = $this->baseBreadcrumbs;
        $bread['Camara'] = null;

        return array(
            'breadcrumbs' => $bread,
            'page_title' => 'Camara'
        );
    }

    /**
     *
     * @Route("/index_table/", name="entrada_camara_table", methods={"GET|POST"})
     * @IsGranted("ROLE_ENTRADA_CAMARA")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_entrada_camara';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('fechaSiembraReal', 'fechaSiembraReal');
        $rsm->addScalarResult('className', 'className');
        $rsm->addScalarResult('colorBandeja', 'colorBandeja');
        $rsm->addScalarResult('colorProducto', 'colorProducto');

        $renderPage = "entrada_camara/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}/pedido_producto_entrada_camara", name="pedido_producto_entrada_camara", methods={"GET","POST"})
     * @Template("pedidoproducto/show/entrada_camara_show.html.twig")
     */
    public function showPedidoProductoEntradaCamaraAction($id) {

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
     *
     * @Route("/guardar/", name="guardar_entrada_camara", methods={"POST"})
     * @IsGranted("ROLE_ENTRADA_CAMARA")
     */
    public function guardarEntradaCamara(Request $request){

        $idPedidoProducto = $request->get('idPedidoProducto');
        $fechaEntradaCamara = $request->get('fechaEntradaCamara');
        $dateTime = new DateTime($fechaEntradaCamara);
        $em = $this->doctrine->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setFechaEntradaCamara($dateTime);
        if ($pedidoProducto->getEstado()->getCodigoInterno() != ConstanteEstadoPedidoProducto::EN_CAMARA) {
            $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::EN_CAMARA);
            $pedidoProducto->setFechaEntradaCamaraReal(new DateTime());
            $this->estadoService->cambiarEstadoPedidoProducto($pedidoProducto, $estado, 'EN CAMARA.');
            $cantDiasEnCamara = new DateTime($fechaEntradaCamara);
            $fechaSalidaCamara= $cantDiasEnCamara->modify($pedidoProducto->getDiasEnCamara());
            $pedidoProducto->setFechaSalidaCamara($fechaSalidaCamara);
            //$pedidoProducto->setFechaSalidaCamaraReal($fechaSalidaCamara);
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
     * @Route("/pedidos-atrasados/", name="entrada_camara_pedidos_atrasados", methods={"GET","POST"})
     */
    public function pedidosAtrasados(): JsonResponse
    {
        /* @var $pedidoProducto PedidoProducto */
        $pedidosProductos = $this->getDoctrine()->getRepository(PedidoProducto::class)->getPedidosAtrasados(ConstanteEstadoPedidoProducto::SEMBRADO);
        $html = $this->renderView('salida_camara/pedidos_atrasados.html.twig', array('pedidosProductos' => $pedidosProductos));

        $result = array(
            'html' => $html,
            'cantidad' => sizeof($pedidosProductos),
        );

        return new JsonResponse($result);
    }
}