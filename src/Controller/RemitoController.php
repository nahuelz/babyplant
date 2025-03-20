<?php

namespace App\Controller;

use Afip;
use App\Entity\Constants\ConstanteEstadoEntrega;
use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\Entrega;
use App\Entity\EntregaProducto;
use App\Entity\EstadoEntrega;
use App\Entity\EstadoEntregaHistorico;
use App\Entity\EstadoRemito;
use App\Entity\EstadoRemitoHistorico;
use App\Entity\Remito;
use App\Form\RemitoType;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectManager;
use Mpdf\Mpdf;
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
        $em = $this->doctrine->getManager();
        $remito = $this->remitoSetData($request);

        $form = $this->createForm(RemitoType::class, $remito);
        $form->handleRequest($request);

        $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PENDIENTE);
        $this->cambiarEstadoRemito($em, $remito, $estadoRemito);
        $estadoEntrega = $em->getRepository(EstadoEntrega::class)->findOneByCodigoInterno(ConstanteEstadoEntrega::CON_REMITO);
        foreach ($remito->getEntregas() as $entrega) {
            $this->cambiarEstadoEntrega($em, $entrega, $estadoEntrega);
        }

        $em->persist($remito);
        $em->flush();
        $message = $this->getCreateMessage($remito, true);
        $this->get('session')->getFlashBag()->add('success', $message);

        return $this->getCreateRedirectResponse($request, $remito);

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
     * @Route("/imprimir-remito/{id}", name="imprimir_remito", methods={"GET"})
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
     * @Route("/imprimir-factura-arca/{id}", name="imprimir_factura_arca", methods={"GET"})
     */
    public function imprimirFacturaArcaAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        // Certificado (Puede estar guardado en archivos, DB, etc)
        $cert = file_get_contents('certificado/prueba.crt');

        // Key (Puede estar guardado en archivos, DB, etc)
        $key = file_get_contents('certificado/prueba');


        // Tu CUIT
        $tax_id = 20382971923;
        $afip = new Afip(array(
            'CUIT' => $tax_id,
            'cert' => $cert,
            'key' => $key
        ));

        $html = $this->render('arca/factura.html.twig', array('remito' => $remito))->getContent();

        // Nombre para el archivo (sin .pdf)
        $name = 'PDF de prueba';

        // Opciones para el archivo
        $options = array(
            "width" => 8, // Ancho de pagina en pulgadas. Usar 3.1 para ticket
            "marginLeft" => 0.4, // Margen izquierdo en pulgadas. Usar 0.1 para ticket
            "marginRight" => 0.4, // Margen derecho en pulgadas. Usar 0.1 para ticket
            "marginTop" => 0.4, // Margen superior en pulgadas. Usar 0.1 para ticket
            "marginBottom" => 0.4 // Margen inferior en pulgadas. Usar 0.1 para ticket
        );

        // Creamos el PDF
        $res = $afip->ElectronicBilling->CreatePDF(array(
            "html" => $html,
            "file_name" => $name,
            "options" => $options
        ));

        // Mostramos la url del archivo creado
        return $this->redirect($res['file']);
    }

    /**
     *
     * @Route("/imprimir-ticket-arca/{id}", name="imprimir_ticket_arca", methods={"GET"})
     */
    public function imprimirTicketArcaAction($id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Remito")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        // Certificado (Puede estar guardado en archivos, DB, etc)
        $cert = file_get_contents('certificado/prueba.crt');

        // Key (Puede estar guardado en archivos, DB, etc)
        $key = file_get_contents('certificado/prueba');


        // Tu CUIT
        $tax_id = 20382971923;
        $afip = new Afip(array(
            'CUIT' => $tax_id,
            'cert' => $cert,
            'key' => $key
        ));

        $html = $this->render('arca/ticket.html.twig', array('remito' => $remito))->getContent();

        // Nombre para el archivo (sin .pdf)
        $name = 'PDF de prueba';

        // Opciones para el archivo
        $options = array(
            "width" => 3.1, // Ancho de pagina en pulgadas. Usar 3.1 para ticket
            "marginLeft" => 0.1, // Margen izquierdo en pulgadas. Usar 0.1 para ticket
            "marginRight" => 0.1, // Margen derecho en pulgadas. Usar 0.1 para ticket
            "marginTop" => 0.1, // Margen superior en pulgadas. Usar 0.1 para ticket
            "marginBottom" => 0.1 // Margen inferior en pulgadas. Usar 0.1 para ticket
        );

        // Creamos el PDF
        $res = $afip->ElectronicBilling->CreatePDF(array(
            "html" => $html,
            "file_name" => $name,
            "options" => $options
        ));

        // Mostramos la url del archivo creado
        return $this->redirect($res['file']);
    }

    /**
     *
     * @return string
     */
    protected function getPrintOutputType() {
        return "I";
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
    public function listaEntregasAction(Request $request): JsonResponse
    {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->getDoctrine()->getRepository(Entrega::class);

        $query = $repository->createQueryBuilder('e')
            ->select("e.id, concat ('Entrega N° ', e.id) as denominacion")
            ->where('e.clienteEntrega = :cliente')
            ->andWhere('e.estado = :estado')
            ->setParameter('cliente', $idCliente)
            ->setParameter('estado', ConstanteEstadoEntrega::SIN_REMITO)
            ->orderBy('e.id', 'ASC')
            ->groupBy('e.id')
            ->getQuery();

        return new JsonResponse($query->getResult());
    }

    /**
     * @Route("/confirmar-remito", name="confirmar_remito", methods={"GET","POST","PUT"})
     * @IsGranted("ROLE_REMITO")
     */
    public function confirmarRemito(Request $request): JsonResponse
    {
        $entity = $this->remitoSetData($request);

        $form = $this->createForm(RemitoType::class, $entity);
        $form->handleRequest($request);
        $result = array(
            'html' => $this->renderView('remito/confirmar_remito.html.twig', array('entity' => $entity)),
            'error' => false
        );

        return new JsonResponse($result);
    }

    private function remitoSetData(Request $request){
        $entity = new Remito();
        $em = $this->doctrine->getManager();
        $entregas = $request->request->get('remito')['entregas'];
        for($i = 0; $i < count($entregas); ++$i) {
            $entrega = $entregas[$i]['entrega'];
            $entregasProductos = $entrega['entregasProductos'];
            for($x = 0; $x < count($entregasProductos); ++$x) {
                /* @var EntregaProducto $entregaProductoEntity */
                $entregaProductoEntity = $em->getRepository('App\Entity\EntregaProducto')->find($entregasProductos[$x]['entregaProducto']);
                $entregaProductoEntity->setPrecioUnitario($entregasProductos[$x]['precioUnitario']);
                $entity->addEntrega($entregaProductoEntity->getEntrega());
            }
        }

        return $entity;
    }

}