<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteAPI;
use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteEstadoEntregaProducto;
use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\Constants\ConstanteTipoDescuento;
use App\Entity\EntregaProducto;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaProducto;
use App\Entity\EstadoRemito;
use App\Entity\Remito;
use App\Entity\Usuario;
use App\Form\RemitoType;
use App\Repository\EntregaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Mpdf\MpdfException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use DateInterval;

/**
 * @Route("/remito")
 * @IsGranted("ROLE_REMITO")
 */
class RemitoController extends BaseController {


    /**
     * @Route("/", name="remito_index", methods={"GET"})
     * @Template("remito/index.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function index(): array
    {

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();


        return array(
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'page_title' => 'Remitos generados'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="remito_table", methods={"GET|POST"})
     * @IsGranted("ROLE_REMITO")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('idRemito', 'idRemito');
        $rsm->addScalarResult('idPedidoProducto', 'idPedidoProducto');
        $rsm->addScalarResult('ordenSiembra', 'ordenSiembra');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('idCliente', 'idCliente');
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('cantidadPlantas', 'cantidadPlantas');
        $rsm->addScalarResult('precioUnitario', 'precioUnitario');
        $rsm->addScalarResult('precioSubTotal', 'precioSubTotal');
        $rsm->addScalarResult('precioTotal', 'precioTotal');
        $rsm->addScalarResult('tipoDescuento', 'tipoDescuento');
        $rsm->addScalarResult('precioTotalConDescuento', 'precioTotalConDescuento');
        $rsm->addScalarResult('montoPendiente', 'montoPendiente');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorProducto', 'colorProducto');
        $rsm->addScalarResult('montoTotalConDescuentoProducto', 'montoTotalConDescuentoProducto');
        $rsm->addScalarResult('montoPendienteProducto', 'montoPendienteProducto');
        $rsm->addScalarResult('montoPagoProducto', 'montoPagoProducto');

        $nativeQuery = $em->createNativeQuery('call sp_index_remito(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('remito/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/new", name="remito_new", methods={"GET","POST"})
     * @Template("remito/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function new(Request $request, EntityManagerInterface $em): Array {
        $entity = new Remito();
        if ($request->query->has('id')) {
            $id = $request->query->get('id');
            $usuario = $em->getRepository(Usuario::class)->find($id);
            $entity->setCliente($usuario);
        }
        return parent::baseNewAction($entity);
    }
    
    /**
     * @inheritDoc
     */
    protected function getExtraParametersNewAction($entity): array
    {
        return [
            'entregas' => [] // Inicializamos entregas como un array vacío
        ];
    }

    /**
     * @Route("/insertar", name="remito_create", methods={"GET","POST"})
     * @Template("remito/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function createAction(Request $request) {
        $em = $this->doctrine->getManager();
        $remito = new Remito();
        $form = $this->createForm(RemitoType::class, $remito);
        $form->handleRequest($request);
        $remito = $this->remitoSetData($request, $remito, $em);
        $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PENDIENTE);
        $this->estadoService->cambiarEstadoRemito($remito, $estadoRemito, 'REMITO CREADO.');
        $estadoEntrega = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::CON_REMITO);
        foreach ($remito->getEntregas() as $entrega) {
            if ($entrega->isEntregado()){
                $estadoEntrega = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::ENTREGADO_CON_REMITO);
            }
            $this->estadoService->cambiarEstadoEntrega($entrega, $estadoEntrega, 'REMITO CREADO.');
            foreach ($entrega->getEntregasProductos() as $entregaProducto) {
                $entregaProducto->setMontoPendiente($entregaProducto->getMontoTotalConDescuento());
                if ($entregaProducto->getMontoPendiente() == 0){
                    $estadoEntregaProducto = $em->getRepository(EstadoEntregaProducto::class)->findOneByCodigoInterno(ConstanteEstadoEntregaProducto::PAGO);
                    $this->estadoService->cambiarEstadoEntregaProducto($entregaProducto, $estadoEntregaProducto, 'PAGO.');
                }
            }
        }
        $totalDeuda = $remito->getCliente()->getCuentaCorrienteUsuario()->getPendiente() + $remito->getTotalConDescuento();
        $remito->setTotalDeuda($totalDeuda);
        $remito->setSaldoCuentaCorriente($remito->getCliente()->getCuentaCorrienteUsuario()->getSaldo());
        if ($remito->getTotalConDescuento() == 0){
            $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PAGO);
            $this->estadoService->cambiarEstadoRemito($remito, $estadoRemito, 'REMITO MONTO 0.');
        }
        $em->persist($remito);
        $em->flush();

        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => 'REMITO CREADO.',
            'id' => $remito->getId(),
            'idCliente' => $remito->getCliente()->getId(),
            'statusCode' => Response::HTTP_OK,
            'statusText' => ConstanteAPI::STATUS_TEXT_OK
        )));

        return $response;

    }

    /**
     * @Route("/{id}", name="remito_show", methods={"GET"})
     * @Template("remito/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="remito_edit", methods={"GET","POST"})
     * @Template("remito/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="remito_update", methods={"PUT"})
     * @Template("remito/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="remito_delete", methods={"GET"})
     * @IsGranted("ROLE_REMITO")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-remito/{id}", name="imprimir_remito", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirRemitoAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito){
            $id = base64_decode($id);
            $remito = $em->getRepository("App\Entity\Remito")->find($id);
        }

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('remito/remito_pdf.html.twig', array('entity' => $remito, 'tipo_pdf' => "REMITO"));
        $filename = "Remito.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-remito-todos/{id}", name="imprimir_remito_todos", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirRemitoTodosAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('remito/remito_todos_pdf.html.twig', array('entity' => $usuario, 'tipo_pdf' => "REMITO"));
        $filename = "Remitos.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-remito-pendientes/{id}", name="imprimir_remito_pendientes", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirRemitoPendientesAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('remito/remito_pendientes_pdf.html.twig', array('entity' => $usuario, 'tipo_pdf' => "REMITO"));
        $filename = "Remitos.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     *
     * @Route("/imprimir-factura-arca/{id}", name="imprimir_factura_arca", methods={"GET"})
     * @throws Exception
     */
    public function imprimirFacturaArcaAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->render('arca/factura.html.twig', array('entity' => $remito))->getContent();

        return $this->printService->printARCA($html, false);
    }

    /**
     *
     * @Route("/imprimir-ticket-arca/{id}", name="imprimir_ticket_arca", methods={"GET"})
     * @throws Exception
     */
    public function imprimirTicketArcaAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->render('arca/ticket.html.twig', array('entity' => $remito))->getContent();

        return $this->printService->printARCA($html);
    }

    /**
     * @Route("/{id}/historico_estados", name="remito_historico_estado", methods={"POST"})
     * @Template("remito/historico_estados.html.twig")
     */
    public function showHistoricoEstadoAction($id): array
    {

        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository('App\Entity\Remito')->find($id);

        if (!$remito) {
            throw $this->createNotFoundException('No se puede encontrar el Remito .');
        }

        return array(
            'entity' => $remito,
            'historicoEstados' => $remito->getHistoricoEstados(),
            'page_title' => 'Histórico de estados'
        );
    }

    /**
     * @Route("/lista/entregas", name="remito_lista_entregas")
     */
    public function listaEntregasAction(Request $request, EntregaRepository $entregaRepository): JsonResponse
    {
        $idCliente = $request->request->get('id_entity');

        $result = $entregaRepository->findEntregasSinRemitoPorCliente((int)$idCliente);

        return new JsonResponse($result);
    }

    /**
     * @Route("/confirmar-remito", name="confirmar_remito", methods={"GET","POST","PUT"})
     * @IsGranted("ROLE_REMITO")
     */
    public function confirmarRemito(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $entity = new Remito();
        $form = $this->createForm(RemitoType::class, $entity);
        $form->handleRequest($request);
        $entity = $this->remitoSetData($request, $entity, $em);
        $result = array(
            'html' => $this->renderView('remito/confirmar_remito.html.twig', array('entity' => $entity)),
            'error' => false
        );

        return new JsonResponse($result);
    }

    /**
     * @Route("/{id}/cancelar", name="remito_cancelar", methods={"GET"})
     * @IsGranted("ROLE_REMITO")
     */
    public function cancelarRemito(Request $request, int $id): Response
    {
        $em = $this->doctrine->getManager();

        /** @var Remito|null $remito */
        $remito = $em->getRepository(Remito::class)->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se encontró el Remito con ID $id.");
        }

        $estadoCancelado = $em->getRepository(EstadoRemito::class)->find(ConstanteEstadoRemito::CANCELADO);

        $this->estadoService->cambiarEstadoRemito($remito, $estadoCancelado, 'CANCELADO.');

        foreach ($remito->getEntregas() as $entrega) {
            $estadoSinRemito = $em->getRepository(EstadoEntrega::class)->find(ConstanteEstadoEntrega::SIN_REMITO);
            $this->estadoService->cambiarEstadoEntrega($entrega, $estadoSinRemito, 'Remito cancelado, vuelve a estado "SIN REMITO"');
        }

        $em->flush();

        $this->addFlash('success', 'El remito fue cancelado correctamente.');

        if ($request->query->get('path')){
            return $this->redirectToRoute($request->query->get('path'), ['id' => $request->query->get('idCliente')]);
        }

        return $this->redirectToRoute('remito_index');
    }

    private function remitoSetData(Request $request, $entity, $em): Remito
    {
        $entregas = $request->request->get('remito')['entregas'];
        $tipoDescuento = $request->request->get('remito')['tipoDescuento'];
        $cantidadDescuento = 0;
        for($i = 0; $i < count($entregas); ++$i) {
            $entrega = $entregas[$i]['entrega'];
            $entregasProductos = $entrega['entregasProductos'];
            for($x = 0; $x < count($entregasProductos); ++$x) {
                /* @var EntregaProducto $entregaProductoEntity */
                $entregaProductoEntity = $em->getRepository('App\Entity\EntregaProducto')->find($entregasProductos[$x]['entregaProducto']);
                $entregaProductoEntity->setPrecioUnitario($entregasProductos[$x]['precioUnitario']);
                $entregaProductoEntity->setCantidadDescuento($entregasProductos[$x]['montoDescuento']);
                $entity->addEntrega($entregaProductoEntity->getEntrega());
                $cantidadDescuento+=$entregaProductoEntity->getCantidadDescuento();
            }
        }
        if ($tipoDescuento == ConstanteTipoDescuento::DESCUENTO_FIJO){
            $entity->setCantidadDescuento($cantidadDescuento);
        }

        return $entity;
    }

}