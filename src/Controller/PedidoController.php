<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoPedido;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\Constants\ConstanteIP;
use App\Entity\EstadoPedido;
use App\Entity\EstadoPedidoHistorico;
use App\Entity\EstadoPedidoProducto;
use App\Entity\EstadoPedidoProductoHistorico;
use App\Entity\GlobalConfig;
use App\Entity\MYPDF;
use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Entity\RazonSocial;
use App\Entity\Usuario;
use App\Form\RazonSocialType;
use App\Form\RegistrationFormType;
use App\Service\LogAuditoriaService;
use DateInterval;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Mpdf\Mpdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pedido")
 */
class PedidoController extends BaseController {

    /**
     * @Route("/save_columns/", name="save_columns", methods={"GET","POST"})
     */
    public function saveColumns(Request $request) {

        $em = $this->doctrine->getManager();

        /* @var $columnas GlobalConfig */
        $columnas = $em->getRepository('App\Entity\GlobalConfig')->find(1);

        if (!$columnas) {
            throw $this->createNotFoundException('No se configuraron las columnas visibles en la base de datos.');
        }

        $columnasOcultas = json_decode($request->request->get('columns'), false);
        $columnas->setColumnasOcultas(implode(",", $columnasOcultas));
        $em->flush();

        $message = 'Se guardó la configuración de columnas.';
        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => $message,
            'statusCode' => 200,
            'statusText' => 'OK'
        )));

        return $response;
    }

    /**
     * @Route("/", name="pedido_index", methods={"GET"})
     * @Template("pedido/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function index(): Array {

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();

        $em = $this->doctrine->getManager();
        $columnasOcultas = $em->getRepository('App\Entity\GlobalConfig')->find(1);

        return array(
            'columnasOcultas' => $columnasOcultas->getColumnasOcultas(),
            'indicadorEstadoData' => $this->getIndicadorEstadoData(),
            'actividadReciente' => $this->getActividadRecienteData(),
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'page_title' => 'Pedidos generados'
        );
    }

    /**
     * Tabla para app_pago.
     *
     * @Route("/index_table/", name="pedido_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $fechaDesde = $request->get('fechaDesde') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaDesde') . ' 00:00:00') : (new DateTime())->sub(new DateInterval('P7D'));
        $fechaHasta = $request->get('fechaHasta') ? DateTime::createFromFormat('d/m/Y H:i:s', $request->get('fechaHasta') . ' 23:59:59') : new DateTime();

        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('idProducto', 'idProducto');
        $rsm->addScalarResult('fechaCreacion', 'fechaCreacion');
        $rsm->addScalarResult('nombreVariedad', 'nombreVariedad');
        $rsm->addScalarResult('nombreProducto', 'nombreProducto');
        $rsm->addScalarResult('nombreSubProducto', 'nombreSubProducto');
        $rsm->addScalarResult('nombreProductoCompleto', 'nombreProductoCompleto');
        $rsm->addScalarResult('cliente', 'cliente');
        $rsm->addScalarResult('cantidadBandejas', 'cantidadBandejas');
        $rsm->addScalarResult('tipoBandeja', 'tipoBandeja');
        $rsm->addScalarResult('fechaSiembraPedido', 'fechaSiembraPedido');
        $rsm->addScalarResult('fechaEntregaPedido', 'fechaEntregaPedido');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('idEstado', 'idEstado');
        $rsm->addScalarResult('colorEstado', 'colorEstado');
        $rsm->addScalarResult('ordenSiembra', 'ordenSiembra');
        $rsm->addScalarResult('mesada', 'mesada');
        $rsm->addScalarResult('diasEnCamara', 'diasEnCamara');
        $rsm->addScalarResult('diasEnInvernaculo', 'diasEnInvernaculo');

        $nativeQuery = $em->createNativeQuery('call sp_index_pedido(?,?,?)', $rsm);

        $nativeQuery->setParameter(1, $fechaDesde, 'datetime');
        $nativeQuery->setParameter(2, $fechaHasta, 'datetime');
        $nativeQuery->setParameter(3, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('pedido/index_table.html.twig', array('entities' => $entities));
    }

    /**
     *
     * @return type
     */
    private function getIndicadorEstadoData() {

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('estado', 'estado');
        $rsm->addScalarResult('cantidad', 'cantidad');
        $rsm->addScalarResult('colorClass', 'colorClass');
        $rsm->addScalarResult('color', 'color');
        $rsm->addScalarResult('iconClass', 'iconClass');

        $estadosValidos = [
            ConstanteEstadoPedidoProducto::PENDIENTE,
            ConstanteEstadoPedidoProducto::PLANIFICADO,
            ConstanteEstadoPedidoProducto::SEMBRADO,
            ConstanteEstadoPedidoProducto::EN_CAMARA,
            ConstanteEstadoPedidoProducto::EN_INVERNACULO,
            ConstanteEstadoPedidoProducto::ENTREGA_PARCIAL,
            ConstanteEstadoPedidoProducto::ENTREGADO_COMPLETO,
            ConstanteEstadoPedidoProducto::CANCELADO
        ];

        $sql = '
            SELECT
                est.nombre AS estado,
                COUNT(pp.id) AS cantidad,
                est.color AS colorClass,
                est.color_icono AS color,
                CASE
                    WHEN est.id = 0 THEN "fa-circle-o-notch"
                    WHEN est.id = 1 THEN "fa-spinner"
                    WHEN est.id = 2 THEN "fa-clipboard-list"
                    WHEN est.id = 3 THEN "fa-leaf"
                    WHEN est.id = 4 THEN "fa-border-all"
                    WHEN est.id = 5 THEN "fa-home"
                    WHEN est.id = 6 THEN "fa-tasks"
                    WHEN est.id = 7 THEN "fa-check"
                    WHEN est.id = 8 THEN "fa-exclamation-triangle"
                    ELSE "fa-check"
                    END AS iconClass,
                est.id
            FROM pedido_producto AS pp
                     INNER JOIN estado_pedido_producto AS est ON pp.id_estado_pedido_producto = est.id
            WHERE pp.fecha_baja IS NULL
              AND est.codigo_interno IN (?)
            GROUP BY est.id';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        $nativeQuery->setParameter(1, $estadosValidos, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);

        return $nativeQuery->getResult();
    }

    /**
     *
     * @return type
     */
    private function getActividadRecienteData() {

        $em = $this->doctrine->getManager();

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('actividad', 'actividad');
        $rsm->addScalarResult('fecha', 'fecha');
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('colorClass', 'colorClass');

        $sql = '
            SELECT
                p.id AS id,
                CONCAT_WS(" ", "El pedido producto nº", LPAD(pp.id, 5, 0), "cambió su estado a", est.nombre) AS actividad,
                h.fecha_creacion AS fecha,
                est.color_icono as colorClass
            FROM estado_pedido_producto_historico AS h
                     INNER JOIN pedido_producto AS pp ON pp.id = h.id_pedido_producto
                     INNER JOIN pedido AS p ON p.id = pp.id_pedido
                     INNER JOIN estado_pedido_producto AS est ON h.id_estado_pedido_producto = est.id
            WHERE pp.fecha_baja IS NULL
              AND h.fecha_baja IS NULL
            ORDER BY h.id DESC
            LIMIT 0, 20';

        $nativeQuery = $em->createNativeQuery($sql, $rsm);

        return $nativeQuery->getResult();
    }

    /**
     * @Route("/new", name="pedido_new", methods={"GET","POST"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function new(): Array {
        return parent::baseNewAction();
    }


    /**
     * @Route("/insertar", name="pedido_create", methods={"GET","POST"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request);
    }

    /**
     * @Route("/{id}", name="pedido_show", methods={"GET"})
     * @Template("pedido/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="pedido_edit", methods={"GET","POST"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="pedido_update", methods={"PUT"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="pedido_delete", methods={"GET"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }

    function execPrePersistAction($entity, $request): bool {
        /** @var Pedido $entity */
        $em = $this->doctrine->getManager();
        $estadoPedido = $em->getRepository(EstadoPedido::class)->findOneByCodigoInterno(ConstanteEstadoPedido::NUEVO);
        $estadoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::PENDIENTE);
        $this->cambiarEstado($em, $entity, $estadoPedido, $estadoProducto);

        /** @var PedidoProducto $pedidoProducto */
        foreach ($entity->getPedidosProductos() as $pedidoProducto){
            $pedidoProducto->setPedido($entity);
            $pedidoProducto->setFechaPedido(new DateTime());
        }
        return true;
    }

    function execPostPersistAction($em, $entity, $request)
    {
        $logAuditoriaService = new LogAuditoriaService($this->getDoctrine());
        $logAuditoriaService->generarLog($entity, 'Crear pedido', 'PEDIDO');
        $em->flush();
    }

    /**
     *
     * @param type $em
     * @param Pedido $pedido
     * @param type $estado
     * @param type $motivo
     */
    private function cambiarEstado($em, Pedido $pedido, $estadoPedido, $estadoProducto) {


        $pedido->setEstado($estadoPedido);
        /* SETEO EL ESTADO DEL PEDIDO */
        $estadoPedidoHistorico = new EstadoPedidoHistorico();
        $estadoPedidoHistorico->setPedido($pedido);
        $estadoPedidoHistorico->setFecha(new DateTime());
        $estadoPedidoHistorico->setEstado($estadoPedido);
        $estadoPedidoHistorico->setMotivo('Creacion del pedido.');
        $pedido->addHistoricoEstado($estadoPedidoHistorico);

        $em->persist($estadoPedidoHistorico);

        /* SETEO EL ESTADO DE CADA UNO DE LOS PRODUCTOS */
        foreach ($pedido->getPedidosProductos() as $pedidosProducto) {
            $pedidosProducto->setEstado($estadoProducto);
            $estadoPedidoProductoHistorico = new EstadoPedidoProductoHistorico();
            $estadoPedidoProductoHistorico->setPedidoProducto($pedidosProducto);
            $estadoPedidoProductoHistorico->setFecha(new DateTime());
            $estadoPedidoProductoHistorico->setEstado($estadoProducto);
            $estadoPedidoProductoHistorico->setMotivo('Creacion del pedido.');
            $pedidosProducto->addHistoricoEstado($estadoPedidoProductoHistorico);

            $em->persist($estadoPedidoProductoHistorico);
        }
    }

    /**
     * @Route("/{id}/historico_estados", name="pedido_historico_estado", methods={"POST"})
     * @Template("pedido/historico_estados.html.twig")
     */
    public function showHistoricoEstadoAction($id) {

        $em = $this->doctrine->getManager();

        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($id);

        if (!$pedidoProducto) {
            throw $this->createNotFoundException('No se puede encontrar el producto.');
        }

        return array(
            'entity' => $pedidoProducto,
            'historicoEstados' => $pedidoProducto->getHistoricoEstados(),
            'page_title' => 'Histórico de estados'
        );
    }

    /**
     *
     * @return Array
     */
    protected function getExtraParametersNewAction($entity): Array
    {
        return $this->getForms();
    }

    private function getForms(): Array
    {
        $user = new Usuario();

        $form = $this->createForm(RegistrationFormType::class, $user, array(
            'action' => $this->generateUrl('app_register_ajax'),
            'method' => 'POST'
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        $razonSocial = new RazonSocial();

        $formRazonSocial = $this->createForm(RazonSocialType::class, $razonSocial, array(
            'action' => $this->generateUrl('app_razonsocial_create_ajax'),
            'method' => 'POST'
        ));

        $formRazonSocial->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return [
            'preserve_values' => true,
            'registrationForm' => $form->createView(),
            'razonSocialForm' => $formRazonSocial->createView()
        ];
    }

    /**
     *
     * @return Array
     */
    protected function getExtraParametersEditAction($entity): Array {
        $user = new Usuario();

        $form = $this->createForm(RegistrationFormType::class, $user, array(
            'action' => $this->generateUrl('app_register_ajax'),
            'method' => 'POST'
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        return [
            'preserve_values' => true,
            'registrationForm' => $form->createView()
        ];
    }

    /**
     * @Route("/{id}/pedido_producto", name="pedido_producto", methods={"GET","POST"})
     * @Template("pedido/producto_show.html.twig")
     */
    public function showPedidoProductoAction($id) {

        $em = $this->doctrine->getManager();

        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($id);

        if (!$pedidoProducto) {
            throw $this->createNotFoundException('No se puede encontrar el producto.');
        }

        return array(
            'entity' => $pedidoProducto,
            'page_title' => 'Pedido Producto'
        );
    }


    /**
     * Print a Pedido Entity.
     *
     * @Route("/imprimir-pedido/{id}", name="imprimir_pedido", methods={"GET"})
     */
    public function imprimirPedidoAction($id) {
        $em = $this->doctrine->getManager();

        $pedido = $em->getRepository("App\Entity\Pedido")->find($id);
        /* @var $pedido Pedido */

        //return new Response($this->renderView('pedido/pedido_pdf.html.twig', array('entity' => $pedido)));

        if (!$pedido) {
            throw $this->createNotFoundException("No se puede encontrar la entidad PEDIDO.");
        }

        $html = $this->renderView('pedido/pedido_pdf.html.twig', array('entity' => $pedido));

        $filename = 'pedido.pdf';

        $pdfService = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdfService->AddPage();
        $pdfService->SetTitle($filename);
        $pdfService->WriteHTML($html);

        // set style for barcode
        $style = array(
            'border' => 0,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );
        $url = $this->generateUrl('pedido_show', array('id' => $pedido->getId()));
        $url = ConstanteIP::LOCAL_IP.$url;
        $pdfService->Text(82, 180, 'Seguimiento del pedido');
        $pdfService->write2DBarcode($url, 'QRCODE,L', 80, 180, 50, 50, $style, 'N');


        $mpdfOutput = $pdfService->Output($filename, 'I');

        return new Response($mpdfOutput);
    }

    /**
     *
     * @return string
     */
    protected function getPrintOutputType() {
// "I" = Inline , "D" = Download
        return "I";
    }
}
