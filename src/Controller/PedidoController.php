<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoMesada;
use App\Entity\Constants\ConstanteEstadoPedidoProducto;
use App\Entity\CuentaCorrientePedido;
use App\Entity\EstadoMesada;
use App\Entity\EstadoPedidoProducto;
use App\Entity\GlobalConfig;
use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Entity\RazonSocial;
use App\Entity\Usuario;
use App\Form\CambiarMesadaType;
use App\Form\RazonSocialType;
use App\Form\RegistrationFormType;
use App\Service\LogAuditoriaService;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EstadoService;

/**
 * @Route("/pedido")
 * @IsGranted("ROLE_PEDIDO")
 */
class PedidoController extends BaseController {

    /**
     * @Route("/", name="pedido_index", methods={"GET"})
     * @Template("pedido/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function index(): array
    {

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();

        $em = $this->doctrine->getManager();
        $columnasOcultas = $em->getRepository('App\Entity\GlobalConfig')->find($this->getUser()->getId());

        /*if (!$columnasOcultas) {
            $columnasOcultas = new GlobalConfig();
            $columnasOcultas->setColumnasOcultas('1,6,10,11,13');
            $columnasOcultas->setUsuario($this->getUser());
            $em->persist($columnasOcultas);
            $em->flush();
        }*/

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
        $rsm->addScalarResult('idCliente', 'idCliente');
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
        $rsm->addScalarResult('celular', 'celular');

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
            ConstanteEstadoPedidoProducto::ENTREGADO,
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
                    WHEN est.id = 9 THEN "fa-check"
                    WHEN est.id = 10 THEN "fa-exclamation-triangle"
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
    public function new(Request $request, EntityManagerInterface $em): Array {
        $entity = new Pedido();

        if ($request->query->has('id')) {
            $id = $request->query->get('id');
            $usuario = $em->getRepository(Usuario::class)->find($id);
            $entity->setCliente($usuario);
        }

        return parent::baseNewAction($entity);
    }

    /**
     * @Route("/insertar", name="pedido_create", methods={"GET","POST"})
     * @Template("pedido/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request, true);
    }


    public function getCreateMessage($entity, $useDecode = false):string{
        return $entity->getId();
    }


    /**
     * @Route("/{id}", name="pedido_show", methods={"GET"})
     * @Template("pedido/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/producto/{id}", name="pedido_producto_show", methods={"GET"})
     * @Template("pedido/show.html.twig")
     */
    public function Productoshow($id): Array {
        $em = $this->doctrine->getManager();

        /* @var $pedidoProducto PedidoProducto */
        $pedidoProducto = $em->getRepository('App\Entity\PedidoProducto')->find($id);

        if (!$pedidoProducto) {
            throw $this->createNotFoundException('No se puede encontrar el producto.');
        }

        $parametros = array(
            'entity' => $pedidoProducto->getPedido(),
            'page_title' => 'Detalle ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersShowAction($pedidoProducto->getPedido()));
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
        $estadoProducto = $em->getRepository(EstadoPedidoProducto::class)->findOneByCodigoInterno(ConstanteEstadoPedidoProducto::PENDIENTE);

        /** @var PedidoProducto $pedidoProducto */
        foreach ($entity->getPedidosProductos() as $pedidoProducto){
            $pedidoProducto->setPedido($entity);
            $pedidoProducto->setFechaPedido(new DateTime());
            $this->estadoService->cambiarEstadoPedidoProducto($pedidoProducto, $estadoProducto, 'CREADO.');
        }

        $cuentaCorrientePedido = new CuentaCorrientePedido();
        $cuentaCorrientePedido->setPedido($entity);
        $entity->setCuentaCorrientePedido($cuentaCorrientePedido);
        $em->persist($cuentaCorrientePedido);
        return true;
    }

    function execPostPersistAction($em, $entity, $request)
    {
        $logAuditoriaService = new LogAuditoriaService($this->getDoctrine());
        $logAuditoriaService->generarLog($entity, 'Crear pedido', 'PEDIDO');
        $em->flush();
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
            'preserve_values' => false,
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
     * @throws MpdfException
     */
    public function imprimirPedidoAction($id) {
        $em = $this->doctrine->getManager();

        $pedido = $em->getRepository("App\Entity\Pedido")->find($id);
        /* @var $pedido Pedido */

        if (!$pedido) {
            throw $this->createNotFoundException("No se puede encontrar la entidad PEDIDO.");
        }

        $html = $this->renderView('pedido/pedido_pdf.html.twig', array('entity' => $pedido));

        $filename = 'pedido.pdf';

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
     * Print a Pedido Entity.
     *
     * @Route("/imprimir-pedido-ticket/{id}", name="imprimir_pedido_ticket", methods={"GET"})
     */
    public function imprimirPedidoTicketAction($id) {
        $em = $this->doctrine->getManager();

        $pedido = $em->getRepository("App\Entity\Pedido")->find($id);
        /* @var $pedido Pedido */

        if (!$pedido) {
            throw $this->createNotFoundException("No se puede encontrar la entidad PEDIDO.");
        }

        $html = $this->renderView('pedido/pedido_ticket_pdf.html.twig', array('entity' => $pedido));

        $filename = 'pedido.pdf';

        $mpdfService = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 1000], // ancho x alto en milímetros
            'margin_left' => 2,
            'margin_right' => 2,
            'margin_top' => 2,
            'margin_bottom' => 2,
            'orientation' => 'P',
        ]);
        $mpdfService->WriteHTML($html);

        // Obtener altura usada en milímetros
        $usedHeight = $mpdfService->y; // posición vertical actual (mm)
        $mpdfService = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, $usedHeight + 20], // ancho x alto en milímetros
            'margin_left' => 2,
            'margin_right' => 2,
            'margin_top' => 2,
            'margin_bottom' => 2,
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
    protected function getPrintOutputType() {
        return "I";
    }

    /**
     * @Route("/{id}/cancelar", name="pedido_producto_cancelar", methods={"GET"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function cancelarPedidoProducto(int $id): Response
    {
        $em = $this->doctrine->getManager();

        /** @var PedidoProducto|null $pedidoProducto */
        $pedidoProducto = $em->getRepository(PedidoProducto::class)->find($id);

        if (!$pedidoProducto) {
            throw $this->createNotFoundException("No se encontró el PedidoProducto con ID $id.");
        }

        $estadoCancelado = $em->getRepository(EstadoPedidoProducto::class)->find(ConstanteEstadoPedidoProducto::CANCELADO);

        $this->estadoService->cambiarEstadoPedidoProducto($pedidoProducto, $estadoCancelado, 'CANCELADO.');

        $em->flush();

        $this->addFlash('success', 'El producto del pedido fue cancelado correctamente.');

        return $this->redirectToRoute('pedido_index');
    }

    /**
     * @Route("/save_columns/", name="save_columns", methods={"GET","POST"})
     */
    public function guardarColumnas(Request $request): Response {

        $em = $this->doctrine->getManager();

        /* @var $columnas GlobalConfig */
        $columnas = $em->getRepository('App\Entity\GlobalConfig')->find($this->getUser()->getId());

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
     * @Route("/{id}/modal-mesada", name="modal_mesada", methods={"GET"})
     */
    public function modalMesada(PedidoProducto $pedidoProducto): Response
    {
        $form = $this->createForm(CambiarMesadaType::class, $pedidoProducto);

        return $this->render('pedido_producto/_modal_mesada.html.twig', [
            'form' => $form->createView(),
            'pedidoProducto' => $pedidoProducto,
        ]);
    }

    /**
     * @Route("/{id}/cambiar-mesada", name="cambiar_mesada", methods={"POST"})
     */
    public function cambiarMesada(Request $request, PedidoProducto $pedidoProducto, EntityManagerInterface $em): Response {
        $form = $this->createForm(CambiarMesadaType::class, $pedidoProducto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $estadoMesada = $em->getRepository(EstadoMesada::class)->findOneByCodigoInterno(ConstanteEstadoMesada::PENDIENTE);
            if ($pedidoProducto->getMesadaDos() && !$pedidoProducto->getMesadaDos()->getCantidadBandejas()) {
                $pedidoProducto->setMesadaDos(null); // evitar persistir si no hay datos
            }else{
                $pedidoProducto->getMesadaDos()->getTipoMesada()->setTipoProducto($pedidoProducto->getTipoProducto());
                $this->estadoService->cambiarEstadoMesada($pedidoProducto->getMesadaDos(), $estadoMesada, 'CAMBIO DE MESADA.');
            }
            $pedidoProducto->getMesadaUno()->getTipoMesada()->setTipoProducto($pedidoProducto->getTipoProducto());
            $this->estadoService->cambiarEstadoMesada($pedidoProducto->getMesadaUno(), $estadoMesada, 'CAMBIO DE MESADA.');
            $em->flush();

            // Si usás AJAX podés retornar JSON, si no, redirigir:
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            // Redirigir o mostrar mensaje
            $this->addFlash('success', 'Mesada actualizada correctamente');
            return $this->redirectToRoute('pedido_index'); // cambiá esta ruta
        }

        return $this->render('pedido_producto/_modal_mesada.html.twig', [
            'form' => $form->createView(),
            'pedidoProducto' => $pedidoProducto,
        ]);
    }
}
