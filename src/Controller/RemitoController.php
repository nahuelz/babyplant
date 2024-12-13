<?php

namespace App\Controller;

use App\Entity\Pedido;
use App\Entity\PedidoProducto;
use App\Entity\Remito;
use App\Entity\RemitoProducto;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
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
        return true;
    }

    /**
     * @Route("/lista/productos", name="lista_productos")
     */
    public function listaProductosAction(Request $request) {
        $idCliente = $request->request->get('id_entity');

        $repository = $this->getDoctrine()->getRepository(PedidoProducto::class);

        $query = $repository->createQueryBuilder('pp')
            ->select("pp.id, concat ('Pedido N° ', p.id, ' Producto N° ',pp.id, ' N° Orden: ',pp.numeroOrden,' ', tp.nombre, ' Bandejas: ',pp.cantBandejasReales,' (x',tb.nombre,')') as denominacion")
            ->leftJoin('pp.pedido', 'p' )
            ->leftJoin('App:TipoVariedad', 'v', Join::WITH, 'pp.tipoVariedad = v')
            ->leftJoin('App:TipoSubProducto', 'sb', Join::WITH, 'v.tipoSubProducto = sb')
            ->leftJoin('App:TipoProducto', 'tp', Join::WITH, 'sb.tipoProducto = tp')
            ->leftJoin('App:TipoBandeja', 'tb', Join::WITH, 'pp.tipoBandeja = tb')
            ->where('p.cliente = :cliente')
            ->setParameter('cliente', $idCliente)
            ->orderBy('pp.id', 'ASC')
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

}