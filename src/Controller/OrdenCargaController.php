<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Entrega;
use App\Entity\EstadoEntrega;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/orden_carga")
 * @IsGranted("ROLE_ORDEN_CARGA")
 */
class OrdenCargaController extends BaseController
{

    /**
     * @Route("/", name="ordencarga_index", methods={"GET"})
     * @Template("orden_carga/index.html.twig")
     * @IsGranted("ROLE_ORDEN_CARGA")
     */
    public function index(): array
    {
        $bread = $this->baseBreadcrumbs;
        $bread['OrdenCarga'] = null;

        return array(
            'breadcrumbs' => $bread,
            'page_title' => 'OrdenCarga'
        );
    }

    /**
     *
     * @Route("/index_table/", name="orden_carga_table", methods={"GET|POST"})
     * @IsGranted("ROLE_ORDEN_CARGA")
     */
    public function indexTableAction(Request $request): Response {

        $entityTable = 'view_orden_carga';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('className', 'className');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('cliente', 'cliente');

        $renderPage = "orden_carga/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="orden_carga_show", methods={"GET","POST"})
     * @Template("orden_carga/show.html.twig")
     */
    public function showPedidoProductoAction($id) {

        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository('App\Entity\Entrega')->find($id);

        if (!$entrega) {
            throw $this->createNotFoundException('No se puede encontrar la entrega.');
        }

        return array(
            'entity' => $entrega,
            'page_title' => 'Orden de carga'
        );
    }

    /**
     *
     * @Route("/cambiar_fecha_orden_carga/", name="cambiar_fecha_orden_carga", methods={"POST"})
     * @IsGranted("ROLE_ORDEN_CARGA")
     */
    public function cambiarFechaOrdenCarga(Request $request){

        $fechaNuevaParam = $request->get('fechaNueva');
        $idEntrega = $request->get('idEntrega');
        $datetime = new DateTime();
        $nuevaFecha = $datetime->createFromFormat('Y-m-d', $fechaNuevaParam);

        $em = $this->doctrine->getManager();
        /* @var $entrega Entrega */
        $entrega = $em->getRepository('App\Entity\Entrega')->find($idEntrega);
        $entrega->setFechaEntrega($nuevaFecha);
        $em->flush();

        $message = 'Se modifico correctamente la fecha de la Entrega N°'.$entrega->getId();
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }

    /**
     *
     * @Route("/entregar/", name="entregar_orden_carga", methods={"POST"})
     * @IsGranted("ROLE_ORDEN_CARGA")
     */
    public function guardarOrdenCarga(Request $request){
        
        $idEntrega = $request->get('idEntrega');

        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository('App\Entity\Entrega')->find($idEntrega);

        if ($entrega->getRemito() != null) {
            $estado = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::ENTREGADO_CON_REMITO);
        }else{
            $estado = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::ENTREGADO_SIN_REMITO);
        }

        $this->estadoService->cambiarEstadoEntrega($entrega, $estado, 'ENTREGADO.');
        $entrega->setEntregado(true);
        $entrega->setFechaEntrega(
            (new \DateTime())->setTime(23, 59, 0)
        );

        $em->flush();

        $message = 'Pedido entregado correctamente';
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }
}