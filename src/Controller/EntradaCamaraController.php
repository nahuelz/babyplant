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
 * @Route("/entrada_camara")
 */
class EntradaCamaraController extends BaseController
{

    /**
     * @Route("/", name="entradacamara_index", methods={"GET"})
     * @Template("entrada_camara/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
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
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_entrada_camara';

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

        $renderPage = "entrada_camara/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="entrada_camara_show", methods={"GET"})
     * @Template("pedido/show.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function show($id): Array {
        $em = $this->getDoctrine()->getManager();

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
     * @Route("/cambiar_fecha_entrada_camara/", name="cambiar_fecha_entrada_camara", methods={"POST"})
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
     * @Route("/guardar/", name="guardar", methods={"POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function setOrdenSiembra(Request $request){

        $codSobre = $request->get('codSobre');
        $idPedidoProducto = $request->get('idPedidoProducto');

        $em = $this->getDoctrine()->getManager();
        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($idPedidoProducto);
        $pedidoProducto->setCodigoSobre($codSobre);
        if ($pedidoProducto->getEstado()->getCodigoInterno() != ConstanteEstadoPedidoProducto::PLANIFICADO) {
            $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::PLANIFICADO);
            $this->cambiarEstado($em, $pedidoProducto, $estado);
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
}