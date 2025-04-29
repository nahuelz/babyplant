<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteEstadoReserva;
use App\Entity\Entrega;
use App\Entity\EntregaProducto;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\EstadoReserva;
use App\Entity\EstadoReservaHistorico;
use App\Entity\PedidoProducto;
use App\Entity\Reserva;
use App\Form\ReservaType;
use App\Service\EntregaService;
use DateInterval;
use DateTime;
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
 * @IsGranted("ROLE_RESERVA")
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
    public function new(): array
    {
        return parent::baseNewAction();
    }

    /**
     * @Route("/insertar", name="reserva_create", methods={"GET","POST"})
     * @Template("reserva/new.html.twig")
     * @IsGranted("ROLE_RESERVA")
     */
    public function createAction(Request $request): RedirectResponse|Response
    {
        return parent::baseCreateAction($request);
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
        $this->cambiarEstadoReserva($em, $entity, $estadoReserva);
        $this->generarHistoricoReserva($em, $entity);
        $entity->getPedidoProducto()->setCantidadBandejasDisponibles();
        $em->flush();
    }

    /**
     *
     * @param ObjectManager $em
     * @param Reserva $reserva
     * @param EstadoReserva $estadoReserva
     */
    private function cambiarEstadoReserva(ObjectManager $em, Reserva $reserva, EstadoReserva $estadoReserva): void
    {
        $reserva->setEstado($estadoReserva);
        $estadoReservaHistorico = new EstadoReservaHistorico();
        $estadoReservaHistorico->setReserva($reserva);
        $estadoReservaHistorico->setFecha(new DateTime());
        $estadoReservaHistorico->setEstado($estadoReserva);
        $estadoReservaHistorico->setMotivo('Reserva de producto');
        $reserva->addHistoricoEstado($estadoReservaHistorico);
    }

    private function generarHistoricoReserva(ObjectManager $em, Reserva $reserva){
        $estadoPedidoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::RESERVADO);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setReserva($reserva);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoPedidoProducto);
        $estadoPedidoProductoHistorico->setMotivo('Reserva de producto');
        $reserva->getPedidoProducto()->addHistoricoEstado($estadoPedidoProductoHistorico);
        $em->persist($estadoPedidoProductoHistorico);
    }

    /**
     * @Route("/lista/productos", name="reserva_lista_productos")
     */
    public function listaProductosAction(Request $request): JsonResponse
    {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->doctrine->getRepository(PedidoProducto::class);

        $query = $repository->createQueryBuilder('pp')
            ->select("pp.id, pp.fechaEntregaPedido, concat ('PEDIDO N° ',pp.id,' ', tp.nombre, ' ', v.nombre,' DISPONIBLES: ',pp.cantidadBandejasDisponibles,' ESTADO: ',e.nombre) as descripcion")
            ->leftJoin('pp.pedido', 'p' )
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:EstadoPedidoProducto', 'e', Join::WITH, 'pp.estado = e')
            ->where('p.cliente = :cliente')
            ->andWhere('pp.estado NOT IN (:estados)')
            ->andWhere('pp.cantidadBandejasDisponibles > 0')
            ->setParameter('cliente', $idCliente)
            ->setParameter('estados', [ConstanteEstadoPedidoProducto::CANCELADO, ConstanteEstadoPedidoProducto::ENTREGADO])
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

        if (!$reserva) {
            throw $this->createNotFoundException('No se puede encontrar el Reserva .');
        }

        $entregaService = new EntregaService();
        $entrega = new Entrega();
        $entreaProducto = new EntregaProducto();
        $entreaProducto->setCantidadBandejas($reserva->getCantidadBandejas());
        $entreaProducto->setPedidoProducto($reserva->getPedidoProducto());
        $entrega->addEntregaProducto($entreaProducto);
        $entrega->setClienteEntrega($reserva->getCliente());
        $entrega->setCliente($reserva->getPedidoProducto()->getPedido()->getCliente());
        $em->persist($entrega);
        $em->flush();
        $result = $entregaService->entregar($em,$entrega);
        $estadoReserva = $em->getRepository(EstadoReserva::class)->findOneByCodigoInterno(ConstanteEstadoReserva::ENTREGADO);
        $this->cambiarEstadoReserva($em, $reserva, $estadoReserva);
        $em->flush();

        return new JsonResponse($result);
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
            $tipoError = 'ERROR PEDIDO PRODUCTO N° ' . $reserva->getPedidoProducto()->getId();
        }

        if ($error){
            $result = array(
                'html' => '',
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

        if (!$reserva) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('reserva/remito_pdf.html.twig', array('entity' => $reserva, 'website' => "http://192.168.0.182/babyplant/public/"));

        $filename = 'reserva.pdf';

        $mpdfService = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 0,
            'default_font' => '',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'orientation' => 'P',
        ]);

        $mpdfService->SetBasePath($this->getParameter('MPDF_BASE_PATH'));

        $mpdfService->SetTitle($filename);

        $mpdfService->WriteHTML($html);

        $mpdfOutput = $mpdfService->Output($filename, $this->getPrintOutputType());


        return new Response($mpdfOutput);
    }

    /**
     *
     * @return string
     */
    protected function getPrintOutputType(): string
    {
        return "I";
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
}