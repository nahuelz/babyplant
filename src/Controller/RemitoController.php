<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoMesada;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\EntregaProducto;
use App\Entity\EstadoMesada;
use App\Entity\EstadoMesadaHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\EstadoRemito;
use App\Entity\EstadoRemitoHistorico;
use App\Entity\Mesada;
use App\Entity\PedidoProducto;
use App\Entity\Remito;
use App\Entity\RemitoProducto;
use App\Form\RemitoType;
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
 * @Route("/remito")
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
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('precioUnitario', 'precioUnitario');
        $rsm->addScalarResult('precioSubTotal', 'precioSubTotal');
        $rsm->addScalarResult('precioTotal', 'precioTotal');

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
    public function new(): Array {
        return parent::baseNewAction();
    }

    /**
     * @Route("/insertar", name="remito_create", methods={"GET","POST"})
     * @Template("remito/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request);
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

    function execPrePersistAction($entity, $request): bool {
        /** @var RemitoProducto $remitoProducto */
        foreach ($entity->getRemitosProductos() as $remitoProducto){
            $remitoProducto->setRemito($entity);
        }
        $em = $this->doctrine->getManager();
        $estado = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PENDIENTE);
        $this->cambiarEstadoRemito($em, $entity, $estado, 'Creacion de remito');
        return true;
    }

    /**
     *
     * @param type $em
     * @param Remito $entity
     */
    function execPostPersistAction($em, $entity, $request): void
    {
        /** @var RemitoProducto $remitoProducto */
        foreach ($entity->getRemitosProductos() as $remitoProducto){

            $bandejasAEntregar = $remitoProducto->getCantBandejas();

            /* @var $pedidoProducto PedidoProducto */
            $pedidoProducto = $remitoProducto->getPedidoProducto();

            $pedidoProducto->setCantBandejasEntregadas(($pedidoProducto->getCantBandejasEntregadas() + $bandejasAEntregar));
            $pedidoProducto->setCantBandejasFaltantes(($pedidoProducto->getCantBandejasFaltantes() - $bandejasAEntregar));

            // SI ENTREGO TODAS LAS BANDEJAS DEL PEDIDO EL ESTADO PASA A ENTREGADO COMPLETO SI NO A ENTREGADO PARCIAL
            if ($pedidoProducto->getCantBandejasFaltantes() == 0){
                $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::ENTREGADO);
                $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::ENTREGADO);
                $pedidoProducto->setFechaEntregaPedidoReal(new DateTime());
            }else{
                $estado = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::ENTREGADO_P);
                $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::ENTREGADO_PARCIAL);
            }

            $entregaProducto = new EntregaProducto();
            $entregaProducto->setPedidoProducto($pedidoProducto);
            $entregaProducto->setRemito($remitoProducto->getRemito());
            $entregaProducto->setCantBandejasEntregadas($bandejasAEntregar);
            $entregaProducto->setCantBandejasPendientes($pedidoProducto->getCantBandejasFaltantes());
            $entregaProducto->setMesadaUno($pedidoProducto->getMesadaUno());
            $entregaProducto->setMesadaDos($pedidoProducto->getMesadaDos());
            $this->entregarBandejas($em, $pedidoProducto, $estadoMesada, $bandejasAEntregar);

            $em->persist($entregaProducto);

            $this->cambiarEstadoPedido($em, $pedidoProducto, $estado, 'Entrega de bandejas');
        }
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
            $this->cambiarEstadoMesada($em, $mesadaUno, $estadoMesada,$pedidoProducto);
        }else{
            $badejasRestantes = $bandejasAEntregar - $bandejasEnMesadaUno;
            $mesadaUno->entregarBandejas($bandejasEnMesadaUno);
            $this->cambiarEstadoMesada($em, $mesadaUno, $estadoMesada);
            // SI QUEDAN MAS BANDEJAS POR ENTREGAR QUE LAS QUE HAY EN LA MESADA HUBO ERROR, SE DESCUENTAN SOLO LAS QUE HAY
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
    private function cambiarEstadoMesada(ObjectManager $em, Mesada $mesada, EstadoMesada $estadoMesada) {

        $mesada->setEstado($estadoMesada);
        $estadoMesadaHistorico = new EstadoMesadaHistorico();
        $estadoMesadaHistorico->setMesada($mesada);
        $estadoMesadaHistorico->setFecha(new DateTime());
        $estadoMesadaHistorico->setEstado($estadoMesada);
        $estadoMesadaHistorico->setCantBandejas($mesada->getCantidadBandejas());
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
    private function cambiarEstadoPedido($em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto, $motivo) {

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
     *
     * @param type $em
     * @param Remito $remito
     * @param EstadoPedidoProducto $estadoProducto
     */
    private function cambiarEstadoRemito($em, Remito $remito, EstadoRemito $estadoRemito, $motivo) {

        $remito->setEstado($estadoRemito);
        $estadoRemitoHistorico = new EstadoRemitoHistorico();
        $estadoRemitoHistorico->setRemito($remito);
        $estadoRemitoHistorico->setFecha(new DateTime());
        $estadoRemitoHistorico->setEstado($estadoRemito);
        $estadoRemitoHistorico->setMotivo($motivo);
        $remito->addHistoricoEstado($estadoRemitoHistorico);

        $em->persist($estadoRemitoHistorico);
    }

    /**
     * @Route("/lista/productos", name="lista_productos")
     */
    public function listaProductosAction(Request $request) {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->getDoctrine()->getRepository(PedidoProducto::class);

        $query = $repository->createQueryBuilder('pp')
            ->select("pp.id, concat ('ORDEN N° ',pp.numeroOrden,' ', tp.nombre,' (x',tb.nombre,') BANDEJAS SEMBRADAS: ',pp.cantBandejasReales,' FALTAN ENTREGAR: ',pp.cantBandejasFaltantes, ' MESADA N° ', tm.nombre) as denominacion")
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
            ->setParameter('estados', [ConstanteEstadoPedidoProducto::EN_INVERNACULO, ConstanteEstadoPedidoProducto::ENTREGADO_P, ConstanteEstadoPedidoProducto::ENTREGADO_PSR, ConstanteEstadoPedidoProducto::ENTREGADO_SR])
            ->orderBy('pp.id', 'ASC')
            ->groupBy('pp.id')
            ->getQuery();

        return new JsonResponse($query->getResult());
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-remito-a4/{id}", name="imprimir_remito", methods={"GET"})
     */
    public function imprimirRemitoAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('remito/remito_pdf.html.twig', array('entity' => $remito, 'website' => "http://192.168.0.182/babyplant/public/"));

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
     * Print a Remito Entity.
     *
     * @Route("/imprimir-remito-a6/{id}", name="imprimir_remito_a6", methods={"GET"})
     */
    public function imprimirRemitoA6Action($id) {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('remito/remitoA6_pdf.html.twig', array('entity' => $remito, 'website' => "http://192.168.0.182/babyplant/public/"));

        $filename = 'remito.pdf';

        //$mpdfService = new mPDF(array('A4-L', 0, '', 10, 5, 5, 5, 5, 5));

        $mpdfService = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A6',
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
     * Print a Remito Entity.
     *
     * @Route("/imprimir-remito-a6L/{id}", name="imprimir_remito_a6L", methods={"GET"})
     */
    public function imprimirRemitoA6LAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('remito/remitoA6L_pdf.html.twig', array('entity' => $remito, 'website' => "http://192.168.0.182/babyplant/public/"));

        $filename = 'remito.pdf';

        $mpdfService = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A6',
            'default_font_size' => 0,
            'default_font' => '',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'orientation' => 'L',
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

    /**
     * @Route("/confirmar-remito", name="confirmar_remito", methods={"GET","POST"})
     * @Template("remito/remito_pdf.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function confirmarRemito(Request $request) {

        $entity = new Remito();
        $form = $this->createForm(RemitoType::class, $entity);
        $form->handleRequest($request);
        $error = false;
        $tipoError = '';
        foreach ($entity->getRemitosProductos() as $remitoProducto) {
            if ($remitoProducto->getCantBandejas() > $remitoProducto->getPedidoProducto()->getCantBandejasFaltantes()) {
                $error = true;
                $tipoError = 'ERROR ORDEN N° '.$remitoProducto->getPedidoProducto()->getNumeroOrdenCompleto();
            }
        }

        if ($error){
            $result = array(
                'html' => '',
                'error' => true,
                'tipo' => $tipoError
            );
        }else {
            $html = $this->renderView('remito/confirmar_remito.html.twig', array('entity' => $entity));
            $result = array(
                'html' => $html,
                'error' => false,
                'tipo' => $tipoError
            );

        }
        return new JsonResponse($result);
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


}