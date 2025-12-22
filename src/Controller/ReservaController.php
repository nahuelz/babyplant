<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteEstadoReserva;
use App\Entity\Entrega;
use App\Entity\EntregaProducto;
use App\Entity\EstadoEntregaProducto;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\EstadoReserva;
use App\Entity\EstadoReservaHistorico;
use App\Entity\PedidoProducto;
use App\Entity\Reserva;
use App\Entity\Usuario;
use App\Form\ReservaType;
use App\Service\EntregaService;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManager;
use Mpdf\Mpdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reserva")
 */
class ReservaController extends BaseController {

    /**
     * @Route("/", name="reserva_index", methods={"GET"})
     * @Template("reserva/index.html.twig")
     * @IsGranted("ROLE_RESERVA")
     */
    public function index(): array
    {

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();


        return array(
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'page_title' => 'Reservas generados'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="reserva_table", methods={"GET|POST"})
     * @IsGranted("ROLE_RESERVA")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('idReserva', 'idReserva');
        $rsm->addScalarResult('idEntrega', 'idEntrega');
        $rsm->addScalarResult('idPedidoProducto', 'idPedidoProducto');
        $rsm->addScalarResult('ordenSiembra', 'ordenSiembra');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('clienteReserva', 'clienteReserva');
        $rsm->addScalarResult('idCliente', 'idCliente');
        $rsm->addScalarResult('idClienteReserva', 'idClienteReserva');
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorProducto', 'colorProducto');
        $rsm->addScalarResult('estadoPedidoProducto', 'estadoPedidoProducto');
        $rsm->addScalarResult('idEstadoPedidoProducto', 'idEstadoPedidoProducto');
        $rsm->addScalarResult('colorEstadoPedidoProducto', 'colorEstadoPedidoProducto');

        $nativeQuery = $em->createNativeQuery('call sp_index_reserva(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('reserva/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/new", name="reserva_new", methods={"GET","POST"})
     * @Template("reserva/new.html.twig")
     * @IsGranted("ROLE_RESERVA")
     */
    public function new(Request $request, EntityManagerInterface $em): Array {

        $entity = new Reserva();

        if ($request->query->has('id')) {
            $id = $request->query->get('id');
            $usuario = $em->getRepository(Usuario::class)->find($id);
            $entity->setCliente($usuario);
        }

        return parent::baseNewAction($entity);
    }

    /**
     * @Route("/insertar", name="reserva_create", methods={"GET","POST"})
     * @Template("reserva/new.html.twig")
     * @IsGranted("ROLE_RESERVA")
     */
    public function createAction(Request $request): RedirectResponse|Response
    {
        return parent::baseCreateAction($request, true);
    }

    /**
     * @Route("/{id}", name="reserva_show", methods={"GET"})
     * @Template("reserva/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="reserva_edit", methods={"GET","POST"})
     * @Template("reserva/new.html.twig")
     * @IsGranted("ROLE_RESERVA")
     */
    public function edit($id): RedirectResponse|array
    {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="reserva_update", methods={"PUT"})
     * @Template("reserva/new.html.twig")
     * @IsGranted("ROLE_RESERVA")
     */
    public function update(Request $request, $id): RedirectResponse|Response
    {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="reserva_delete", methods={"GET"})
     * @IsGranted("ROLE_RESERVA")
     */
    public function delete($id): RedirectResponse|JsonResponse
    {
        return parent::baseDeleteAction($id);
    }

    /**
     *
     * @param ObjectManager $em
     * @param Reserva $entity
     */
    function execPostPersistAction($em, $entity, $request): void
    {
        $estadoReserva = $em->getRepository(EstadoReserva::class)->findOneByCodigoInterno(ConstanteEstadoReserva::SIN_ENTREGAR);
        $this->estadoService->cambiarEstadoReserva($entity, $estadoReserva,'RESERVA CREADA.');
        $entity->getPedidoProducto()->setCantidadBandejasDisponibles();
        $em->flush();
    }

    /**
     * @Route("/lista/productos", name="reserva_lista_productos")
     */
    public function listaProductosAction(Request $request): JsonResponse
    {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->doctrine->getRepository(PedidoProducto::class);

        $query = $repository->createQueryBuilder('pp')
            ->select("pp.id, pp.fechaEntregaPedido, concat ('PEDIDO N° ',p.id, ' ORDEN N° ',pp.numeroOrden,' ', tp.nombre, ' ', v.nombre,' DISPONIBLES: ',pp.cantidadBandejasDisponibles,' ESTADO: ',e.nombre) as descripcion")
            ->leftJoin('pp.pedido', 'p' )
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:EstadoPedidoProducto', 'e', Join::WITH, 'pp.estado = e')
            ->where('p.cliente = :cliente')
            ->andWhere('pp.estado NOT IN (:estados)')
            ->andWhere('pp.cantidadBandejasDisponibles > 0')
            ->setParameter('cliente', $idCliente)
            ->setParameter('estados', [ConstanteEstadoPedidoProducto::CANCELADO,ConstanteEstadoPedidoProducto::PENDIENTE, ConstanteEstadoPedidoProducto::ENTREGADO])
            ->orderBy('pp.id', 'ASC')
            ->groupBy('pp.id')
            ->getQuery();

        $resultados = $query->getResult();

        $datosFormateados = array_map(function ($row) {
            return [
                'id' => $row['id'],
                'denominacion' => $row['descripcion'] . ' FECHA ENTREGA: ' . $row['fechaEntregaPedido']->format('d-m-Y'),
            ];
        }, $resultados);

        return new JsonResponse($datosFormateados);
    }

    /**
     * @Route("/{id}/realizar-entrega", name="realizar_entrega", methods={"GET","POST", "PUT"})
     * @IsGranted("ROLE_RESERVA")
     * @Template("entrega/confirmar_entrega.html.twig")
     */
    public function realizarEntrega($id): JsonResponse
    {
        $em = $this->doctrine->getManager();

        /* @var $reserva Reserva */
        $reserva = $em->getRepository('App\Entity\Reserva')->find($id);
        $error = false;

        if (!$reserva) {
            $this->get('session')->getFlashBag()->add('error','No se puede encontrar la Reserva .');
            $error = true;
        }

        if ( in_array($reserva->getPedidoProducto()->getEstado()->getCodigoInterno(), [ConstanteEstadoPedidoProducto::EN_CAMARA,  ConstanteEstadoPedidoProducto::PLANIFICADO, ConstanteEstadoPedidoProducto::SEMBRADO])){
            $msg = "No se puede entregar un producto que se encuentra en estado ". strtoupper($reserva->getPedidoProducto()->getEstado());
            $this->get('session')->getFlashBag()->add('error', $msg);
            $error = true;
        }

        if (!$error) {
            $entrega = new Entrega();
            $entreaProducto = new EntregaProducto();
            $entreaProducto->setEntrega($entrega);
            $entreaProducto->setCantidadBandejas($reserva->getCantidadBandejas());
            $entreaProducto->setPedidoProducto($reserva->getPedidoProducto());
            $entreaProducto->setMontoPendiente($entreaProducto->getMontoTotalConDescuento());
            $estadoEntregaProducto = $em->getRepository(EstadoEntregaProducto::class)->findOneByCodigoInterno(ConstanteEstadoReserva::ENTREGADO);
            $this->estadoService->cambiarEstadoEntregaProducto($entreaProducto, $estadoEntregaProducto, 'ENTREGA');
            $entrega->addEntregaProducto($entreaProducto);
            $entrega->setClienteEntrega($reserva->getCliente());
            $entrega->setCliente($reserva->getPedidoProducto()->getPedido()->getCliente());
            $entreaProducto->setEntrega($entrega);
            $em->persist($entreaProducto);
            $em->persist($entrega);
            $em->flush();
            $entregaService = new EntregaService();
            $entregaService->entregar($em, $entrega);
            $estadoReserva = $em->getRepository(EstadoReserva::class)->findOneByCodigoInterno(ConstanteEstadoReserva::ENTREGADO);
            $this->estadoService->cambiarEstadoReserva($reserva, $estadoReserva, 'ENTREGA');
            $reserva->setEntrega($entrega);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Producto entregado.");
        }

        return new JsonResponse($error);
    }

    /**
     * @Route("/{id}/reserva-confirmar-entrega", name="reserva_confirmar_entrega", methods={"GET","POST", "PUT"})
     * @IsGranted("ROLE_RESERVA")
     * @Template("entrega/confirmar_entrega.html.twig")
     */
    public function confirmarEntrega($id): array
    {
        $em = $this->doctrine->getManager();

        /* @var $reserva Reserva */
        $reserva = $em->getRepository('App\Entity\Reserva')->find($id);

        if (!$reserva) {
            throw $this->createNotFoundException('No se puede encontrar el Reserva .');
        }

        $entrega = new Entrega();
        $entreaProducto = new EntregaProducto();
        $entreaProducto->setCantidadBandejas($reserva->getCantidadBandejas());
        $entreaProducto->setPedidoProducto($reserva->getPedidoProducto());
        $entrega->addEntregaProducto($entreaProducto);
        $entrega->setClienteEntrega($reserva->getCliente());
        $entrega->setCliente($reserva->getPedidoProducto()->getPedido()->getCliente());

        return array(
            'entity' => $entrega,
            'page_title' => 'Entregar'
        );
    }

    /**
     * @Route("/confirmar-reserva", name="confirmar_reserva", methods={"GET","POST", "PUT"})
     * @IsGranted("ROLE_RESERVA")
     */
    public function confirmarReserva(Request $request): JsonResponse
    {

        $reserva = new Reserva();
        $form = $this->createForm(ReservaType::class, $reserva);
        $form->handleRequest($request);
        $error = false;
        $tipoError = '';
        if ($reserva->getCantidadBandejas() > $reserva->getPedidoProducto()->getCantidadBandejasDisponibles()) {
            $error = true;
            $tipoError = 'ERROR PEDIDO N° ' . $reserva->getPedidoProducto()->getPedido()->getId();
        }

        if ($error){
            $result = array(
                'html' => $tipoError,
                'error' => true,
                'tipo' => $tipoError
            );
        }else {
            $result = array(
                'html' => $this->renderView('reserva/confirmar_reserva.html.twig', array('entity' => $reserva)),
                'error' => false,
                'tipo' => $tipoError
            );

        }
        return new JsonResponse($result);
    }

    /**
     * @Route("/{id}/historico_estados", name="reserva_historico_estado", methods={"POST"})
     * @Template("reserva/historico_estados.html.twig")
     */
    public function showHistoricoEstadoAction($id): array
    {

        $em = $this->doctrine->getManager();

        /* @var $reserva Reserva */
        $reserva = $em->getRepository('App\Entity\Reserva')->find($id);

        if (!$reserva) {
            throw $this->createNotFoundException('No se puede encontrar el Reserva .');
        }

        return array(
            'entity' => $reserva,
            'historicoEstados' => $reserva->getHistoricoEstados(),
            'page_title' => 'Histórico de estados'
        );
    }

    /**
     * Print a Reserva Entity.
     *
     * @Route("/imprimir-reserva/{id}", name="imprimir_reserva", methods={"GET"})
     */
    public function imprimirReservaAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $reserva Reserva */
        $reserva = $em->getRepository("App\Entity\Reserva")->find($id);

        if (!$reserva){
            $id = base64_decode($id);
        }

        /* @var $reserva Reserva */
        $reserva = $em->getRepository("App\Entity\Reserva")->find($id);

        if (!$reserva) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('reserva/reserva_pdf.html.twig', array('entity' => $reserva, 'tipo_pdf' => "RESERVA"));
        $filename = "Reserva.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Pedido Entity.
     *
     * @Route("/imprimir-reserva-ticket/{id}", name="imprimir_reserva_ticket", methods={"GET"})
     */
    public function imprimirReservaTicketAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $reserva Reserva */
        $reserva = $em->getRepository("App\Entity\Reserva")->find($id);

        if (!$reserva) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('reserva/reserva_ticket_pdf.html.twig', array('entity' => $reserva, 'tipo_pdf' => "RESERVA"));
        $filename = "Reserva.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printTicket($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * @Route("/get-reserva", name="get_reserva", methods={"GET","POST"})
     */
    public function getReserva(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $idReserva = $request->request->get('id');
        $reserva = $em->getRepository("App\Entity\Reserva")->find($idReserva);
        $productos = [];
        foreach ($reserva->getReservasProductos() as $reservaProducto) {
            $productos[] = [
                'idReserva' => $reserva->getId(),
                'idReservaProducto' => $reservaProducto->getId(),
                'idProducto' => $reservaProducto->getPedidoProducto()->getId(),
                'textProducto' => $reservaProducto->getPedidoProducto()->__toString(),
                'cantidadBandejas' => $reservaProducto->getCantidadBandejas()
            ];
        }

        $result = array(
            'productos' => $productos
        );

        return new JsonResponse($result);
    }

    public function getCreateMessage($entity, $useDecode = false):string{
        return $entity->getId();
    }

    /**
     * @Route("/{id}/cancelar", name="reserva_cancelar", methods={"GET"})
     */
    public function cancelarReserva(Request $request, int $id): Response
    {
        $em = $this->doctrine->getManager();

        /** @var Reserva|null $reserva */
        $reserva = $em->getRepository(Reserva::class)->find($id);

        if (!$reserva) {
            throw $this->createNotFoundException("No se encontró la reserva con ID $id.");
        }

        $estadoCancelado = $em->getRepository(EstadoReserva::class)->find(ConstanteEstadoReserva::CANCELADO);

        $this->estadoService->cambiarEstadoReserva($reserva, $estadoCancelado, 'CANCELADO.');

        $em->flush();

        $this->addFlash('success', 'La reserva fue cancelada correctamente.');

        if ($request->query->get('path')){
            return $this->redirectToRoute($request->query->get('path'), ['id' => $request->query->get('idCliente')]);
        }

        return $this->redirectToRoute('reserva_index');
    }
}