<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\Entrega;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\EstadoRemito;
use App\Entity\EstadoRemitoHistorico;
use App\Entity\PedidoProducto;
use App\Entity\Remito;
use App\Form\EntregaType;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManager;
use Mpdf\Mpdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use DateInterval;

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
        $rsm->addScalarResult('idCliente', 'idCliente');
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('precioUnitario', 'precioUnitario');
        $rsm->addScalarResult('precioSubTotal', 'precioSubTotal');
        $rsm->addScalarResult('precioTotal', 'precioTotal');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');

        $nativeQuery = $em->createNativeQuery('call sp_index_remito(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('remito/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/new/{id}", name="remito_new", methods={"GET","POST"})
     * @Template("entrega/remito/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function new($id): Array {
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository("App\Entity\Entrega")->find($id);

        $this->baseInitPreCreateForm($entity);

        $form = $this->baseInitCreateCreateForm(EntregaType::class, $entity);

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        $parametros = array(
            'entity' => $entity,
            'form' => $form->createView(),
            'form_action' => $this->getURLPrefix() . '_create',
            'page_title' => 'Agregar ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersNewAction($entity));
    }

    /**
     * @Route("/insertar/{id}", name="remito_create", methods={"GET","POST"})
     * @Template("entrega/remito/new.html.twig")
     * @IsGranted("ROLE_REMITO")
     */
    public function createAction($id,Request $request) {
        $em = $this->doctrine->getManager();
        $entrega = $em->getRepository("App\Entity\Entrega")->find($id);

        $remito = new Remito();
        $remito->setCliente($entrega->getClienteEntrega());
        $entrega->setRemito($remito);

        $form = $this->baseInitCreateCreateForm(EntregaType::class, $entrega);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $remito = new Remito();
            $remito->setCliente($entrega->getClienteEntrega());
            $entrega->setRemito($remito);
            $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PENDIENTE);
            $this->cambiarEstadoRemito($em, $remito, $estadoRemito);
            $estadoEntrega = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::CON_REMITO);
            $this->cambiarEstadoEntrega($em, $entrega, $estadoEntrega);
            $em->persist($remito);
            $em->persist($entrega);
            $em->flush();

            $message = $this->getCreateMessage($entrega, true);
            $this->get('session')->getFlashBag()->add('success', $message);
            return $this->getCreateRedirectResponse($request, $entrega);
        } else {
            $request->attributes->set('form-error', true);
        }

        $parametros = array(
            'entity' => $entrega,
            'form' => $form->createView(),
            'page_title' => 'Remito'
        );

        return array_merge($parametros, $this->getExtraParametersNewAction($entrega));
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
     *
     * @param type $em
     * @param PedidoProducto $pedidoProducto
     * @param EstadoPedidoProducto $estadoProducto
     */
    private function cambiarEstadoPedido($em, PedidoProducto $pedidoProducto, EstadoPedidoProducto $estadoProducto, $DatosEntrega = null) {
        $pedidoProducto->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
        $estadoPedidoProductoHistorico->setPedidoProducto($pedidoProducto);
        $estadoPedidoProductoHistorico->setFecha(new DateTime());
        $estadoPedidoProductoHistorico->setEstado($estadoProducto);
        $estadoPedidoProductoHistorico->setMotivo('Entrega de bandejas');
        $estadoPedidoProductoHistorico->setDatosEntrega($DatosEntrega);
        $pedidoProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

        $em->persist($estadoPedidoProductoHistorico);
    }

    /**
     *
     * @param ObjectManager $em
     * @param Remito $remito
     * @param EstadoRemito $estadoRemito
     */
    private function cambiarEstadoRemito(ObjectManager $em, Remito $remito, EstadoRemito $estadoRemito) : void {

        $remito->setEstado($estadoRemito);
        $estadoRemitoHistorico = new EstadoRemitoHistorico();
        $estadoRemitoHistorico->setRemito($remito);
        $estadoRemitoHistorico->setFecha(new DateTime());
        $estadoRemitoHistorico->setEstado($estadoRemito);
        $estadoRemitoHistorico->setMotivo('Creacion de remito');
        $remito->addHistoricoEstado($estadoRemitoHistorico);

        $em->persist($estadoRemitoHistorico);
    }

    /**
     *
     * @param ObjectManager $em
     * @param Entrega $entrega
     * @param EstadoEntrega $estadoEntrega
     */
    private function cambiarEstadoEntrega(ObjectManager $em, Entrega $entrega, EstadoEntrega $estadoEntrega): void
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
     *
     * @param type $entity
     */
    protected function baseInitPreCreateForm($entity) {
        $remito = new Remito();
        $remito->setCliente($entity->getClienteEntrega());
        $entity->setRemito($remito);
    }

    /**
     * @Route("/confirmar-entrega-remito", name="confirmar_entrega_remito", methods={"GET","POST", "PUT"})
     * @IsGranted("ROLE_REMITO")
     */
    public function confirmarEntregaRemito(Request $request) {
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
     *
     * @param type $entity
     * @param type $request
     * @return bool
     */
    protected function execPrePersistAction($entity, $request): bool {
        return true;
    }
}