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
        $rsm->addScalarResult('preparada', 'preparada');

        $renderPage = "orden_carga/index_table.html.twig";
        return parent::baseIndexTableAction($request, [], $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    /**
     * @Route("/{id}", name="orden_carga_show", methods={"GET","POST"}, requirements={"id"="\d+"})
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
    public function cambiarFechaOrdenCarga(Request $request): JsonResponse
    {
        $fechaNuevaParam = (string) $request->request->get('fechaNueva');
        $idEntrega = $request->request->getInt('idEntrega');
        $nuevaFecha = DateTime::createFromFormat('!Y-m-d', $fechaNuevaParam);

        if (!$nuevaFecha || $nuevaFecha->format('Y-m-d') !== $fechaNuevaParam) {
            return new JsonResponse(['message' => 'La fecha indicada no es válida.'], Response::HTTP_BAD_REQUEST);
        }

        $em = $this->doctrine->getManager();
        $entrega = $em->getRepository(Entrega::class)->find($idEntrega);

        if (!$entrega) {
            return new JsonResponse(['message' => 'No se encontró la entrega.'], Response::HTTP_NOT_FOUND);
        }

        $entrega->setFechaEntrega($nuevaFecha);
        $em->flush();

        return new JsonResponse([
            'status' => 'OK',
            'message' => 'Se modificó correctamente la fecha de la Entrega N°' . $entrega->getId(),
        ]);
    }

    /**
     * @Route("/{id}/cambiar-preparada", name="orden_carga_cambiar_preparada", methods={"POST"}, requirements={"id"="\d+"})
     * @IsGranted("ROLE_ORDEN_CARGA")
     */
    public function cambiarPreparada(Request $request, Entrega $entrega): JsonResponse
    {
        if (!$this->isCsrfTokenValid('cambiar_preparada_' . $entrega->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['message' => 'El formulario expiró. Vuelva a intentarlo.'], Response::HTTP_BAD_REQUEST);
        }

        $entrega->setPreparada(!$entrega->isPreparada());
        $this->doctrine->getManager()->flush();

        return new JsonResponse([
            'message' => $entrega->isPreparada()
                ? 'La orden de carga fue marcada como preparada.'
                : 'La orden de carga fue marcada como no preparada.',
            'preparada' => $entrega->isPreparada(),
        ]);
    }

    /**
     * @Route("/{id}/cancelar-entrega", name="orden_carga_cancelar_entrega", methods={"POST"}, requirements={"id"="\d+"})
     * @IsGranted("ROLE_ORDEN_CARGA")
     */
    public function cancelarEntrega(Request $request, Entrega $entrega): JsonResponse
    {
        if (!$this->isCsrfTokenValid('cancelar_entrega_' . $entrega->getId(), (string) $request->request->get('_token'))) {
            return new JsonResponse(['message' => 'El formulario expiró. Vuelva a intentarlo.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            if (
                !$entrega->getEstado()
                || $entrega->getEstado()->getCodigoInterno() !== ConstanteEstadoEntrega::ENTREGADO_SIN_REMITO
            ) {
                throw new \DomainException('Solo se pueden cancelar órdenes entregadas sin remito.');
            }

            $em = $this->doctrine->getManager();
            $estadoSinRemito = $em->getRepository(EstadoEntrega::class)
                ->findOneByCodigoInterno(ConstanteEstadoEntrega::SIN_REMITO);

            if (!$estadoSinRemito) {
                throw new \DomainException('No se encontró el estado SIN REMITO.');
            }

            $entrega->setEntregado(false);
            $entrega->setUsuarioEntrega(null);
            $this->estadoService->cambiarEstadoEntrega(
                $entrega,
                $estadoSinRemito,
                'Se cancela la entrega desde la orden de carga.'
            );
            $em->flush();

            return new JsonResponse(['message' => 'La entrega fue cancelada correctamente.']);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
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
        $entrega->setUsuarioEntrega($this->getUser());

        $em->flush();

        $message = 'Pedido entregado correctamente';
        $result = array(
            'status' => 'OK',
            'message' => $message
        );

        return new JsonResponse($result);

    }
}