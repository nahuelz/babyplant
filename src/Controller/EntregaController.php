<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteAPI;
use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteEstadoMesada;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\DatosEntrega;
use App\Entity\EntregaProducto;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaHistorico;
use App\Entity\EstadoMesada;
use App\Entity\EstadoMesadaHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\Mesada;
use App\Entity\PedidoProducto;
use App\Entity\Entrega;
use App\Entity\Remito;
use App\Form\EntregaType;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManager;
use Mpdf\Mpdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use DateInterval;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/entrega")
 */
class EntregaController extends BaseController {

    /**
     * @Route("/", name="entrega_index", methods={"GET"})
     * @Template("entrega/index.html.twig")
     * @IsGranted("ROLE_REMITO")
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
     * @IsGranted("ROLE_REMITO")
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
        $rsm->addScalarResult('idCliente', 'idCliente');
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');

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
     * @IsGranted("ROLE_REMITO")
     */
    public function new(): Array {
        return parent::baseNewAction();
    }

    /**
     * @Route("/insertar", name="entrega_create", methods={"GET","POST"})
     * @Template("entrega/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function createAction(Request $request) {
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
     * @IsGranted("ROLE_REMITO")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="entrega_update", methods={"PUT"})
     * @Template("entrega/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="entrega_delete", methods={"GET"})
     * @IsGranted("ROLE_REMITO")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }

    function execPrePersistAction($entity, $request): bool {
        /** @var DatosEntrega $datosEntrega */
        foreach ($entity->getEntregasProductos() as $datosEntrega){
            $datosEntrega->setEntrega($entity);
        }
        return true;
    }

    /**
     *
     * @param type $em
     * @param Entrega $entity
     */
    function execPostPersistAction($em, $entity, $request): void
    {
        /** @var EntregaProducto $entregaProducto */
        foreach ($entity->getEntregasProductos() as $entregaProducto){

            $bandejasAEntregar = $entregaProducto->getCantidadBandejas();

            /* @var $pedidoProducto PedidoProducto */
            $pedidoProducto = $entregaProducto->getPedidoProducto();

            $cantidadBandejasEntregadas = ($pedidoProducto->getCantidadBandejasEntregadas() + $bandejasAEntregar);
            $cantidadBandejasSinEntregar = ($pedidoProducto->getCantidadBandejasSinEntregar() - $bandejasAEntregar);

            // SI ENTREGO TODAS LAS BANDEJAS DEL PEDIDO EL ESTADO PASA A ENTREGADO COMPLETO SI NO A ENTREGADO PARCIAL
            if ($cantidadBandejasSinEntregar == 0){
                $estadoPedidoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::ENTREGADO);
                $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::ENTREGADO);
                $pedidoProducto->setFechaEntregaPedidoReal(new DateTime());
            }else{
                $estadoPedidoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL);
                $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::ENTREGADO_PARCIAL);
            }

            $pedidoProducto->setCantidadBandejasEntregadas($cantidadBandejasEntregadas);
            $pedidoProducto->setCantidadBandejasSinEntregar($cantidadBandejasSinEntregar);

            $datosEntrega = new DatosEntrega();
            $datosEntrega->setPedidoProducto($pedidoProducto);
            $datosEntrega->setEntrega($entity);
            $datosEntrega->setCantidadBandejasEntregadas($cantidadBandejasEntregadas);
            $datosEntrega->setCantidadBandejasSinEntregar($cantidadBandejasSinEntregar);
            $datosEntrega->setCantidadBandejasAEntregar($bandejasAEntregar);
            $datosEntrega->setMesadaUno($pedidoProducto->getMesadaUno());
            $datosEntrega->setMesadaDos($pedidoProducto->getMesadaDos());
            $this->entregarBandejas($em, $pedidoProducto, $estadoMesada, $bandejasAEntregar);
            $em->persist($datosEntrega);

            $this->cambiarEstadoPedido($em, $pedidoProducto, $estadoPedidoProducto, $datosEntrega);

            $em->flush();
        }
        $estadoEntrega = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::SIN_REMITO);
        $this->cambiarEstadoEntrega($em, $entity, $estadoEntrega);
        $em->flush();
    }

    public function entregarBandejas($em, $pedidoProducto, $estadoMesada, $bandejasAEntregar): void
    {
        $mesadaUno = $pedidoProducto->getMesadaUno();
        $mesadaDos = $pedidoProducto->getMesadaDos();
        $bandejasEnMesadaUno = $mesadaUno != null ? $mesadaUno->getCantidadBandejas() : null;
        $bandejasEnMesadaDos = $mesadaDos != null ? $mesadaDos->getCantidadBandejas() : null;

        if($bandejasEnMesadaUno >= $bandejasAEntregar){
            $mesadaUno->entregarBandejas($bandejasAEntregar);
            $this->cambiarEstadoMesada($em, $mesadaUno, $estadoMesada);
        }else{
            $badejasRestantes = $bandejasAEntregar - $bandejasEnMesadaUno;
            $mesadaUno->entregarBandejas($bandejasEnMesadaUno);
            $this->cambiarEstadoMesada($em, $mesadaUno, $estadoMesada);
            // SI QUEDAN MÁS BANDEJAS POR ENTREGAR QUE LAS QUE HAY EN LA MESADA HUBO ERROR, SE DESCUENTAN SOLO LAS QUE HAY
            if ($badejasRestantes > $bandejasEnMesadaDos){
                $badejasRestantes = $bandejasEnMesadaDos;
            }
            $mesadaDos->entregarBandejas($badejasRestantes);
            $this->cambiarEstadoMesada($em, $mesadaDos, $estadoMesada);
        }
    }

    /**
     *
     * @param ObjectManager $em
     * @param Mesada $mesada
     * @param EstadoMesada $estadoMesada
     */
    private function cambiarEstadoMesada(ObjectManager $em, Mesada $mesada, EstadoMesada $estadoMesada): void
    {
        $mesada->setEstado($estadoMesada);
        $estadoMesadaHistorico = new EstadoMesadaHistorico();
        $estadoMesadaHistorico->setMesada($mesada);
        $estadoMesadaHistorico->setFecha(new DateTime());
        $estadoMesadaHistorico->setEstado($estadoMesada);
        $estadoMesadaHistorico->setCantidadBandejas($mesada->getCantidadBandejas());
        $estadoMesadaHistorico->setMotivo('Entrega de producto.');
        $mesada->addHistoricoEstado($estadoMesadaHistorico);

        $em->persist($estadoMesadaHistorico);
    }

    /**
     *
     * @param type $em
     * @param PedidoProducto $pedidoProducto
     * @param EstadoPedidoProducto $estadoProducto
     */
    private function cambiarEstadoPedido($em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto, $datosEntrega = null): void
    {
        $pedidoProducto->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico->setMotivo('Entrega de bandejas');
        $estadoPedidoProductoHistorico->setDatosEntrega($datosEntrega);
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $em->persist($estadoPedidoProductoHistorico);
    }

    /**
     *
     * @param type $em
     * @param Entrega $entrega
     * @param EstadoEntrega $estadoEntrega
     */
    private function cambiarEstadoEntrega($em, Entrega $entrega, EstadoEntrega $estadoEntrega): void
    {
        $entrega->setEstado($estadoEntrega);
        $estadoEntregaHistorico = new EstadoEntregaHistorico();
        $estadoEntregaHistorico->setEntrega($entrega);
        $estadoEntregaHistorico->setFecha(new DateTime());
        $estadoEntregaHistorico->setEstado($estadoEntrega);
        $estadoEntregaHistorico->setMotivo('Entrega de producto');
        $entrega->addHistoricoEstado($estadoEntregaHistorico);

        $em->persist($estadoEntregaHistorico);
    }

    /**
     * @Route("/lista/productos", name="entrega_lista_productos")
     */
    public function listaProductosAction(Request $request) {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->getDoctrine()->getRepository(PedidoProducto::class);

        $query = $repository->createQueryBuilder('pp')
            ->select("pp.id, concat ('ORDEN N° ',pp.numeroOrden,' ', tp.nombre,' (x',tb.nombre,') BANDEJAS SEMBRADAS: ',pp.cantidadBandejasReales,' SIN ENTREGAR: ',pp.cantidadBandejasSinEntregar, ' MESADA N° ', tm.nombre) as denominacion")
            ->leftJoin('pp.pedido', 'p' )
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:TipoBandeja', 'tb', Join::WITH, 'pp.tipoBandeja = tb')
            ->leftJoin('App:Mesada', 'm', Join::WITH, 'm.pedidoProducto = pp')
            ->leftJoin('App:TipoMesada', 'tm', Join::WITH, 'm.tipoMesada = tm')
            ->where('p.cliente = :cliente')
            ->andWhere('pp.estado IN (:estados)')
            ->setParameter('cliente', $idCliente)
            ->setParameter('estados', [ConstanteEstadoPedidoProducto::EN_INVERNACULO, ConstanteEstadoPedidoProducto::ENTREGADO_PARCIAL])
            ->orderBy('pp.id', 'ASC')
            ->groupBy('pp.id')
            ->getQuery();

        return new JsonResponse($query->getResult());
    }

    /**
     * @Route("/confirmar-entrega", name="confirmar_entrega", methods={"GET","POST", "PUT"})
     * @IsGranted("ROLE_REMITO")
     */
    public function confirmarEntrega(Request $request) {

        $entity = new Entrega();
        $form = $this->createForm(EntregaType::class, $entity);
        $form->handleRequest($request);
        $error = false;
        $tipoError = '';
        foreach ($entity->getEntregasProductos() as $entregaProducto) {
            if ($entregaProducto->getCantidadBandejas() > $entregaProducto->getPedidoProducto()->getCantidadBandejasSinEntregar()) {
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
     */
    public function imprimirEntregaAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $entrega Entrega */
        $entrega = $em->getRepository("App\Entity\Entrega")->find($id);

        if (!$entrega) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('entrega/remito_pdf.html.twig', array('entity' => $entrega, 'website' => "http://192.168.0.182/babyplant/public/"));

        $filename = 'remito.pdf';

        //$mpdfService = new mPDF(array('A4-L', 0, '', 10, 5, 5, 5, 5, 5));

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

        $mpdfService->shrink_tables_to_fit = 1;

        $mpdfService->SetTitle($filename);

        $mpdfService->WriteHTML($html);

        $mpdfOutput = $mpdfService->Output($filename, $this->getPrintOutputType());

        return new Response($mpdfOutput);
    }

    /**
     *
     * @return string
     */
    protected function getPrintOutputType() {
        return "I";
    }
}