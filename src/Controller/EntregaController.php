<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteEstadoEntregaProducto;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\EntregaProducto;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaProducto;
use App\Entity\EstadoRemito;
use App\Entity\PedidoProducto;
use App\Entity\Entrega;
use App\Entity\Remito;
use App\Entity\Usuario;
use App\Form\EntregaType;
use App\Service\EntregaService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManager;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use DateInterval;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/entrega")
 * @IsGranted("ROLE_ENTREGA")
 *
 */
class EntregaController extends BaseController {

    /**
     * @Route("/", name="entrega_index", methods={"GET"})
     * @Template("entrega/index.html.twig")
     * @IsGranted("ROLE_ENTREGA")
     */
    public function index(): array
    {

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();


        return array(
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'page_title' => 'Entregas generados'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="entrega_table", methods={"GET|POST"})
     * @IsGranted("ROLE_ENTREGA")
     * @throws DateInvalidOperationException
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();
        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('idEntrega', 'idEntrega');
        $rsm->addScalarResult('idPedidoProducto', 'idPedidoProducto');
        $rsm->addScalarResult('ordenSiembra', 'ordenSiembra');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('clienteEntrega', 'clienteEntrega');
        $rsm->addScalarResult('idCliente', 'idCliente');
        $rsm->addScalarResult('idClienteEntrega', 'idClienteEntrega');
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('colorProducto', 'colorProducto');

        $nativeQuery = $em->createNativeQuery('call sp_index_entrega(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('entrega/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/new", name="entrega_new", methods={"GET","POST"})
     * @Template("entrega/new.html.twig")
     * @IsGranted("ROLE_ENTREGA")
     */
    public function new(Request $request, EntityManagerInterface $em): Array {
        $entity = new Entrega();

        if ($request->query->has('id')) {
            $id = $request->query->get('id');
            $usuario = $em->getRepository(Usuario::class)->find($id);
            $entity->setCliente($usuario);
            if (!str_contains($usuario->getNombreCompleto(), 'STOCK')) {
                $entity->setClienteEntrega($usuario);
            }
        }

        return parent::baseNewAction($entity);
    }

    /**
     * @Route("/insertar", name="entrega_create", methods={"GET","POST"})
     * @Template("entrega/new.html.twig")
     * @IsGranted("ROLE_ENTREGA")
     */
    public function createAction(Request $request): RedirectResponse|Response
    {
        return parent::baseCreateAction($request);
    }

    /**
     * @Route("/{id}", name="entrega_show", methods={"GET"})
     * @Template("entrega/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="entrega_edit", methods={"GET","POST"})
     * @Template("entrega/new.html.twig")
     * @IsGranted("ROLE_ENTREGA")
     */
    public function edit($id): RedirectResponse|array
    {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="entrega_update", methods={"PUT"})
     * @Template("entrega/new.html.twig")
     * @IsGranted("ROLE_ENTREGA")
     */
    public function update(Request $request, $id): RedirectResponse|Response
    {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="entrega_delete", methods={"GET"})
     * @IsGranted("ROLE_ENTREGA")
     */
    public function delete($id): RedirectResponse|JsonResponse
    {
        return parent::baseDeleteAction($id);
    }

    function execPrePersistAction($entity, $request): bool {
        $em = $this->doctrine->getManager();
        /** @var EntregaProducto $entregaProducto */
        foreach ($entity->getEntregasProductos() as $entregaProducto){
            $entregaProducto->setEntrega($entity);
            $estadoEntregaProducto = $em->getRepository(EstadoEntregaProducto::class)->findOneByCodigoInterno(ConstanteEstadoEntregaProducto::PENDIENTE);
            $this->estadoService->cambiarEstadoEntregaProducto($entregaProducto, $estadoEntregaProducto, 'PENDIENTE.');
        }
        return true;
    }

    /**
     *
     * @param ObjectManager $em
     * @param Entrega $entity
     */
    function execPostPersistAction($em, $entity, $request): void
    {
        $entregaService = new EntregaService();
        $entregaService->entregar($em, $entity);
    }

    /**
     * @Route("/lista/productos", name="entrega_lista_productos")
     */
    public function listaProductosAction(Request $request): JsonResponse
    {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->doctrine->getRepository(PedidoProducto::class);

        $query = $repository->createQueryBuilder('pp')
            ->select("pp.id, concat ('PEDIDO N° ', p.id, ' ORDEN N° ',pp.numeroOrden,' ', tp.nombre, ' ', v.nombre,' (x',tb.nombre,') DISPONIBLES: ',pp.cantidadBandejasDisponibles, ' MESADA N° ', tm.nombre, ' ADELANTO: $',ccp.saldo) as denominacion")
            ->leftJoin('pp.pedido', 'p' )
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:TipoBandeja', 'tb', Join::WITH, 'pp.tipoBandeja = tb')
            ->leftJoin('App:Mesada', 'm', Join::WITH, 'm.pedidoProducto = pp')
            ->leftJoin('App:TipoMesada', 'tm', Join::WITH, 'm.tipoMesada = tm')
            ->leftJoin('App:CuentaCorrientePedido', 'ccp', Join::WITH, 'p.cuentaCorrientePedido = ccp')
            ->where('p.cliente = :cliente')
            ->andWhere('pp.estado IN (:estados)')
            ->andWhere('pp.cantidadBandejasDisponibles > 0')
            ->setParameter('cliente', $idCliente)
            ->setParameter('estados', [ConstanteEstadoPedidoProducto::EN_INVERNACULO, ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL])
            ->orderBy('pp.id', 'ASC')
            ->groupBy('pp.id')
            ->getQuery();

        return new JsonResponse($query->getResult());
    }

    /**
     * @Route("/confirmar-entrega", name="confirmar_entrega", methods={"GET","POST", "PUT"})
     * @IsGranted("ROLE_ENTREGA")
     */
    public function confirmarEntrega(Request $request): JsonResponse
    {

        $entity = new Entrega();
        $form = $this->createForm(EntregaType::class, $entity);
        $form->handleRequest($request);
        $error = false;
        $tipoError = '';
        foreach ($entity->getEntregasProductos() as $entregaProducto) {
            if ($entregaProducto->getCantidadBandejas() > $entregaProducto->getPedidoProducto()->getCantidadBandejasDisponibles()) {
                $error = true;
                $tipoError = 'ERROR ORDEN N° '.$entregaProducto->getPedidoProducto()->getNumeroOrdenCompleto();
            }
        }

        if ($error){
            $result = array(
                'html' => '',
                'error' => true,
                'tipo' => $tipoError
            );
        }else {
            $result = array(
                'html' => $this->renderView('entrega/confirmar_entrega.html.twig', array('entity' => $entity)),
                'error' => false,
                'tipo' => $tipoError
            );

        }
        return new JsonResponse($result);
    }

    /**
     * @Route("/{id}/historico_estados", name="entrega_historico_estado", methods={"POST"})
     * @Template("entrega/historico_estados.html.twig")
     */
    public function showHistoricoEstadoAction($id): array
    {

        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository('App\Entity\Entrega')->find($id);

        if (!$entrega) {
            throw $this->createNotFoundException('No se puede encontrar el Entrega .');
        }

        return array(
            'entity' => $entrega,
            'historicoEstados' => $entrega->getHistoricoEstados(),
            'page_title' => 'Histórico de estados'
        );
    }

    /**
     * Print a Entrega Entity.
     *
     * @Route("/imprimir-entrega/{id}", name="imprimir_entrega", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirEntregaAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository("App\Entity\Entrega")->find($id);

        if (!$entrega) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('entrega/entrega_pdf.html.twig', array('entity' => $entrega, 'tipo_pdf' => "ENTREGA"));
        $filename = "Entrega_interno.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Entrega Entity.
     *
     * @Route("/imprimir-entrega-ticket/{id}", name="imprimir_entrega_ticket", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirEntregaTicketAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository("App\Entity\Entrega")->find($id);

        if (!$entrega) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('entrega/entrega_ticket_pdf.html.twig', array('entity' => $entrega, 'tipo_pdf' => "ENTREGA"));
        $filename = "Entrega_ticket.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printTicket($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Entrega Entity.
     *
     * @Route("/imprimir-entrega-interno-ticket/{id}", name="imprimir_entrega_interno_ticket", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirEntregaInternoTicketAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository("App\Entity\Entrega")->find($id);

        if (!$entrega) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('entrega/interno_interno_pdf.html.twig', array('entity' => $entrega, 'tipo_pdf' => "ENTREGA"));
        $filename = "Entrega_ticket.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printTicket($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Entrega Entity.
     *
     * @Route("/imprimir-entrega-interno/{id}", name="imprimir_entrega_interno", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirEntregaInternoAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository("App\Entity\Entrega")->find($id);

        if (!$entrega) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('entrega/interno_pdf.html.twig', array('entity' => $entrega, 'tipo_pdf' => "ENTREGA INTERNO"));
        $filename = "Entrega_interno.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * @Route("/remito/new/{id}", name="entrega_remito_new", methods={"GET","POST"})
     * @Template("entrega/remito/new.html.twig")
     * @IsGranted("ROLE_ENTREGA")
     */
    public function remitoNew($id): Array {
        $em = $this->doctrine->getManager();

        $entity = $em->getRepository("App\Entity\Entrega")->find($id);

        $this->baseInitPreCreateForm($entity);

        $form = $this->createForm(EntregaType::class, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_remito_create', array('id' => $entity->getId())),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        $parametros = array(
            'entity' => $entity,
            'form' => $form->createView(),
            'form_action' => $this->getURLPrefix() . '_remito_create',
            'page_title' => 'Agregar ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersNewAction($entity));
    }

    /**
     * @Route("/remito/insertar/{id}", name="entrega_remito_create", methods={"GET","POST"})
     * @Template("entrega/remito/new.html.twig")
     * @IsGranted("ROLE_ENTREGA")
     */
    public function remitoCreateAction($id,Request $request): RedirectResponse|array
    {
        $em = $this->doctrine->getManager();
        $entrega = $em->getRepository("App\Entity\Entrega")->find($id);

        $form = $this->baseInitCreateCreateForm(EntregaType::class, $entrega);

        $form->handleRequest($request);

        $remito = $entrega->getRemito();
        foreach ($entrega->getEntregasProductos() as $entregasProducto) {
            $entregasProducto->actualizarMontoPendiente();
        }
        $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PENDIENTE);
        $this->estadoService->cambiarEstadoRemito($remito, $estadoRemito, 'REMITO CREADO.');
        $estadoEntrega = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::CON_REMITO);
        $this->estadoService->cambiarEstadoEntrega($entrega, $estadoEntrega, 'REMITO CREADO.');
        $em->persist($remito);
        $em->flush();

        $message = $this->getCreateMessage($entrega, true);
        $this->get('session')->getFlashBag()->add('success', $message);

        return $this->getCreateRedirectResponse($request, $entrega);

    }

    /**
     *
     * @param Entrega $entity
     */
    protected function baseInitPreCreateForm($entity): void
    {
        $remito = new Remito();
        $remito->setCliente($entity->getClienteEntrega());
        $remito->addEntrega($entity);
    }

    /**
     * @Route("/confirmar-entrega-remito", name="confirmar_entrega_remito", methods={"GET","POST","PUT"})
     * @IsGranted("ROLE_ENTREGA")
     */
    public function confirmarEntregaRemito(Request $request): JsonResponse
    {
        $entity = new Entrega();
        $form = $this->createForm(EntregaType::class, $entity);
        $form->handleRequest($request);
        $result = array(
            'html' => $this->renderView('entrega/remito/confirmar_remito.html.twig', array('entity' => $entity)),
            'error' => false
        );

        return new JsonResponse($result);
    }

    /**
     * @Route("/get-entrega", name="get_entrega", methods={"GET","POST"})
     */
    public function getEntrega(Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $idEntrega = $request->request->get('id');
        $entrega = $em->getRepository("App\Entity\Entrega")->find($idEntrega);
        $productos = [];
        if ($entrega) {
            foreach ($entrega->getEntregasProductos() as $entregaProducto) {
                $productos[] = [
                    'idEntrega' => $entrega->getId(),
                    'idEntregaProducto' => $entregaProducto->getId(),
                    'idProducto' => $entregaProducto->getPedidoProducto()->getId(),
                    'textProducto' => $entregaProducto->getPedidoProducto()->__toString(),
                    'cantidadBandejas' => $entregaProducto->getCantidadBandejas(),
                    'adelanto' => $entregaProducto->getPedidoProducto()->getAdelanto()
                ];
            }
        }

        $result = array(
            'productos' => $productos
        );

        return new JsonResponse($result);
    }
}