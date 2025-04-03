<?php

namespace App\Controller;

use App\Entity\CuentaCorriente;
use App\Entity\ModoPago;
use App\Entity\Movimiento;
use App\Entity\PedidoProducto;
use App\Entity\Remito;
use App\Entity\TipoMovimiento;
use App\Entity\TipoReferencia;
use App\Entity\Usuario;
use Doctrine\ORM\Query\ResultSetMapping;
use Mpdf\Mpdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/situacion_cliente")
 * @IsGranted("ROLE_PEDIDO")
 */
class SituacionClienteController extends BaseController {

    /**
     * @Route("/", name="situacioncliente_index", methods={"GET"})
     * @Template("situacion_cliente/index.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function index(): array
    {
        $clienteSelect = $this->getSelectService()->getClienteFilter();

        return array(
            'clienteSelect' => $clienteSelect,
            'page_title' => 'Situacion Cliente'
        );
    }

    /**
     *
     * @Route("/index_table/", name="situacion_cliente_table", methods={"GET|POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function indexTableAction(Request $request): Response {

        $em = $this->doctrine->getManager();

        $cliente = $request->get('idCliente') ?: NULL;

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('email', 'email');
        $rsm->addScalarResult('nombre', 'nombre');
        $rsm->addScalarResult('apellido', 'apellido');
        $rsm->addScalarResult('cuit', 'cuit');
        $rsm->addScalarResult('telefono', 'telefono');
        $rsm->addScalarResult('celular', 'celular');
        $rsm->addScalarResult('razonSocial', 'razonSocial');

        $nativeQuery = $em->createNativeQuery('call sp_index_situacion_cliente(?)', $rsm);

        $nativeQuery->setParameter(1, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('situacion_cliente/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/new", name="situacion_cliente_new", methods={"GET","POST"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function new(): Array {
        return parent::baseNewAction();
    }


    /**
     * @Route("/insertar", name="situacion_cliente_create", methods={"GET","POST"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function createAction(Request $request) {
        return parent::baseCreateAction($request);
    }

    /**
     * @Route("/{id}", name="situacioncliente_show", methods={"GET"})
     * @Template("situacion_cliente/show.html.twig")
     */
    public function show($id): Array {
        $em = $this->doctrine->getManager();
        $entity = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$entity) {
            throw $this->createNotFoundException("No se puede encontrar el usuario.");
        }

        if ($entity->getCuentaCorriente() == null){
            $cuentaCorriente = new CuentaCorriente();
            $cuentaCorriente->setCliente($entity);
            $entity->setCuentaCorriente($cuentaCorriente);
            $em->persist($cuentaCorriente);
            $em->flush();
        }

        $breadcrumbs = $this->getShowBaseBreadcrumbs($entity);

        $parametros = array(
            'entity' => $entity,
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Detalle ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersShowAction($entity));
    }

    /**
     * @Route("/{id}/edit", name="situacion_cliente_edit", methods={"GET","POST"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="situacion_cliente_update", methods={"PUT"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_PEDIDO")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="situacion_cliente_delete", methods={"GET"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }
    /**
     * @Route("/movimiento/new", name="movimiento_new", methods={"GET","POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function movimientoNewAction(Request $request): Response
    {
        $movimiento = new Movimiento();
        $id = $request->request->get('idCuentaCorriente');

        $em = $this->doctrine->getManager();
        /* @var $cuentaCorriente CuentaCorriente */
        $cuentaCorriente = $em->getRepository("App\Entity\CuentaCorriente")->find($id);
        $cuentaCorriente->addMovimiento($movimiento);
        $form = $this->baseCreateCreateForm($movimiento);

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'modal' => true
        ]);
    }


    /**
     *
     * @param string $entityFormTypeClassName
     * @param type $entity
     * @return type
     */
    protected function baseInitCreateCreateForm($entityFormTypeClassName, $entity): FormInterface {
        return $this->createForm($entityFormTypeClassName, $entity, array(
            'action' => $this->generateUrl($this->getURLPrefix() . '_create'),
            'method' => 'POST',
            'idCliente' => $entity->getCuentaCorriente()->getCliente()->getId(),
        ));
    }



    /**
     * @Route("/movimiento/create", name="situacioncliente_create", methods={"GET","POST"})
     * @IsGranted("ROLE_PEDIDO")
     */
    public function movimientoCreateAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $monto = $request->request->get('monto');
        $modoPagoValue = $request->request->get('modoPago');
        $descripcion = $request->request->get('descripcion');
        $id = $request->request->get('idCuentaCorriente');
        $idPedidoProducto = $request->request->get('idPedidoProducto');

        if ((isset($modoPagoValue) and $modoPagoValue !== '') and (isset($monto) and $monto !== '')) {
            $modoPago = $em->getRepository(ModoPago::class)->findOneByCodigoInterno($modoPagoValue);
            $tipoMovimiento = $em->getRepository(TipoMovimiento::class)->findOneByCodigoInterno(1); // 1 = INGRESO CC
            $tipoReferencia = $em->getRepository(TipoReferencia::class)->findOneByCodigoInterno(1); // 1 = ADELANTO
            $pedidoProducto = $em->getRepository(PedidoProducto::class)->find($idPedidoProducto);

            /* @var $cuentaCorriente CuentaCorriente */
            $cuentaCorriente = $em->getRepository("App\Entity\CuentaCorriente")->find($id);

            $movimiento = new Movimiento();
            $movimiento->setMonto($monto);
            $movimiento->setModoPago($modoPago);
            $movimiento->setDescripcion($descripcion);
            $movimiento->setTipoMovimiento($tipoMovimiento);
            $movimiento->setTipoReferencia($tipoReferencia);
            $movimiento->setPedidoProducto($pedidoProducto);
            $cuentaCorriente->addMovimiento($movimiento);
            $em->persist($movimiento);
            $em->flush();
        }

        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => 'SALDO AGREGADO',
            'statusCode' => 200,
            'statusText' => 'OK'
        )));

        return $response;

    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-movimiento/{id}", name="imprimir_comprobante_movimiento", methods={"GET"})
     */
    public function imprimirComprobanteMovimientoAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $remito Remito */
        $remito = $em->getRepository("App\Entity\Movimiento")->find($id);

        if (!$remito) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('situacion_cliente/movimiento_pdf.html.twig', array('entity' => $remito, 'website' => "http://192.168.0.182/babyplant/public/"));

        $filename = 'pago.pdf';

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
     * @Route("/imprimir-comprobante-movimiento-todos/{id}", name="imprimir_comprobante_movimiento_todos", methods={"GET"})
     */
    public function imprimirComprobanteMovimientoTodosAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('situacion_cliente/movimiento_todos_pdf.html.twig', array('entity' => $usuario, 'website' => "http://192.168.0.182/babyplant/public/"));

        $filename = 'pago.pdf';

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