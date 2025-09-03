<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteTipoMovimiento;
use App\Entity\CuentaCorrientePedido;
use App\Entity\CuentaCorrienteUsuario;
use App\Entity\ModoPago;
use App\Entity\Movimiento;
use App\Entity\Pedido;
use App\Entity\Remito;
use App\Entity\Reserva;
use App\Entity\TipoMovimiento;
use App\Entity\TipoReferencia;
use App\Entity\Usuario;
use App\Form\MovimientoType;
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
 * @IsGranted("ROLE_SITUACION_CLIENTE")
 */
class SituacionClienteController extends BaseController {

    /**
     * @Route("/", name="situacioncliente_index", methods={"GET"})
     * @Template("situacion_cliente/index.html.twig")
     * @IsGranted("ROLE_SITUACION_CLIENTE")
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
     * @IsGranted("ROLE_SITUACION_CLIENTE")
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
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function new(): Array {
        return parent::baseNewAction();
    }


    /**
     * @Route("/insertar", name="situacion_cliente_create", methods={"GET","POST"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_SITUACION_CLIENTE")
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

        if ($entity->getCuentaCorrienteUsuario() == null) {
            $cuentaCorrienteUsuario = new CuentaCorrienteUsuario();
            $cuentaCorrienteUsuario->setCliente($entity);
            $entity->setCuentaCorrienteUsuario($cuentaCorrienteUsuario);
            $em->persist($cuentaCorrienteUsuario);
            $em->flush();
        }

        foreach ($entity->getPedidos() as $pedido) {
            if ($pedido->getCuentaCorrientePedido() == null) {
                $cuentaCorrientePedido = new CuentaCorrientePedido();
                $cuentaCorrientePedido->setPedido($pedido);
                $pedido->setCuentaCorrientePedido($cuentaCorrientePedido);
                $em->persist($cuentaCorrientePedido);
                $em->flush();
            }
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
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="situacion_cliente_update", methods={"PUT"})
     * @Template("situacion_cliente/new.html.twig")
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="situacion_cliente_delete", methods={"GET"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }
    /**
     * @Route("/movimiento/new", name="movimiento_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function movimientoNewAction(Request $request): Response
    {
        $movimiento = new Movimiento();

        $id = $request->request->get('idCuentaCorrienteUsuario');

        $em = $this->doctrine->getManager();
        /* @var $cuentaCorrienteUsuario CuentaCorrienteUsuario */
        $cuentaCorrienteUsuario = $em->getRepository("App\Entity\CuentaCorrienteUsuario")->find($id);
        $cuentaCorrienteUsuario->addMovimiento($movimiento);

        $form = $this->createForm(MovimientoType::class, $movimiento, array(
            'action' => 'adelanto_create',
            'method' => 'POST'
        ));

        return $this->render('situacion_cliente/cuenta_corriente_form.html.twig', [
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
            'action' => 'movimiento_create',
            'method' => 'POST',
            'idCliente' => $entity->getCuentaCorrienteUsuario()->getCliente()->getId(),
        ));
    }



    /**
     * @Route("/movimiento/create", name="movimiento_create", methods={"GET","POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function movimientoCreateAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $monto = $request->request->get('monto');
        $modoPagoValue = $request->request->get('modoPago');
        $descripcion = $request->request->get('descripcion');
        $id = $request->request->get('idCuentaCorrienteUsuario');
        $idMovimiento = '';

        if ((isset($modoPagoValue) and $modoPagoValue !== '') and (isset($monto) and $monto !== '')) {
            $modoPago = $em->getRepository(ModoPago::class)->findOneByCodigoInterno($modoPagoValue);
            $tipoMovimiento = $em->getRepository(TipoMovimiento::class)->findOneByCodigoInterno(ConstanteTipoMovimiento::INGRESO_CC);

            /* @var $cuentaCorrienteUsuario CuentaCorrienteUsuario */
            $cuentaCorrienteUsuario = $em->getRepository("App\Entity\CuentaCorrienteUsuario")->find($id);

            $movimiento = new Movimiento();
            $movimiento->setMonto($monto);
            $movimiento->setModoPago($modoPago);
            $movimiento->setDescripcion($descripcion);
            $movimiento->setTipoMovimiento($tipoMovimiento);
            $cuentaCorrienteUsuario->addMovimiento($movimiento);
            $movimiento->setSaldoCuenta($cuentaCorrienteUsuario->getSaldo());
            $em->persist($movimiento);
            $em->flush();
            $idMovimiento = $movimiento->getId();

            $msg = 'SALDO AGREGADO';
            $code = 200;
            $text = 'OK';
        } else {
            $msg = 'ERROR PARAMETROS';
            $code = 400;
            $text = 'ERROR';
        }

        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => $msg,
            'statusCode' => $code,
            'statusText' => $text,
            'id' => $idMovimiento
        )));

        return $response;
    }

    /**
     * @Route("/adelanto/new", name="adelanto_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoNewAction(Request $request): Response
    {
        $movimiento = new Movimiento();
        $idCliente = $request->request->get('idCliente');

        $form = $this->createForm(MovimientoType::class, $movimiento, array(
            'action' => 'adelanto_create',
            'method' => 'POST',
            'idCliente' => $idCliente,
        ));

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'modal' => true
        ]);
    }

    /**
     * @Route("/adelanto/create", name="adelanto_create", methods={"GET","POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoCreateAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $monto = $request->request->get('monto');
        $modoPagoValue = $request->request->get('modoPago');
        $descripcion = $request->request->get('descripcion');
        $idPedido = $request->request->get('idPedido');
        $idMovimiento = '';

        if ((isset($modoPagoValue) and $modoPagoValue !== '') and (isset($monto) and $monto !== '') and (isset($idPedido) and $idPedido !== '')) {
            $modoPago = $em->getRepository(ModoPago::class)->findOneByCodigoInterno($modoPagoValue);
            $tipoMovimiento = $em->getRepository(TipoMovimiento::class)->findOneByCodigoInterno(ConstanteTipoMovimiento::ADELANTO);
            $pedido = $em->getRepository(Pedido::class)->find($idPedido);

            /* @var $cuentaCorrientePedido CuentaCorrientePedido */
            $cuentaCorrientePedido = $pedido->getCuentaCorrientePedido();

            $movimiento = new Movimiento();
            $movimiento->setMonto($monto);
            $movimiento->setModoPago($modoPago);
            $movimiento->setDescripcion($descripcion);
            $movimiento->setTipoMovimiento($tipoMovimiento);
            $movimiento->setPedido($pedido);
            $cuentaCorrientePedido->addMovimiento($movimiento);
            $movimiento->setSaldoCuenta($cuentaCorrientePedido->getSaldo());
            $em->persist($movimiento);
            $em->flush();
            $idMovimiento = $movimiento->getId();
        }

        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => 'ADELANTO AGREGADO',
            'statusCode' => 200,
            'statusText' => 'OK',
            'id' => $idMovimiento
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

        /* @var $movimiento Movimiento */
        $movimiento = $em->getRepository("App\Entity\Movimiento")->find($id);

        if (!$movimiento) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('situacion_cliente/movimiento_pdf.html.twig', array('entity' => $movimiento, 'tipo_pdf' => "MOVIMIENTO"));
        $filename = "Movimiento.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-movimiento-ticket/{id}", name="imprimir_comprobante_movimiento_ticket", methods={"GET"})
     */
    public function imprimirComprobanteMovimientoTicketAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $movimiento Movimiento */
        $movimiento = $em->getRepository("App\Entity\Movimiento")->find($id);

        if (!$movimiento) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('situacion_cliente/movimiento_ticket_pdf.html.twig', array('entity' => $movimiento, 'tipo_pdf' => "MOVIMIENTO"));
        $filename = "Movimiento.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printTicket($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-movimiento-todos/{id}", name="imprimir_comprobante_movimiento_todos", methods={"GET"})
     */
    public function imprimirComprobanteMovimientoTodosAction($id)
    {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }
        $movimientos = $usuario->getCuentaCorrienteUsuario()->getMovimientos();
        $remitos = $usuario->getRemitos();

        $items = [];

        /*foreach ($movimientos as $m) {
            $items[] = [
                'fechaCreacion' => $m->getFechaCreacion(),
                'tipoMovimiento' => $m->getTipoMovimiento(),
                'monto' => $m->getMonto(),
                'modoPago' => $m->getModoPago(),
                'saldoCuenta' => $m->getSaldoCuenta(),
            ];
        }*/
        foreach ($remitos as $remito) {

            $items[] = [
                'fechaCreacion' => $remito->getFechaCreacion(),
                'tipoMovimiento' => 'REMITO N° ' . $remito->getId(),
                'monto' => -$remito->getTotalConDescuento(),
                'modoPago' => null,
                'saldoCuenta' => null,
            ];

            foreach ($remito->getPagos() as $pagos) {
                $items[] = [
                    'fechaCreacion' => $pagos->getFechaCreacion(),
                    'tipoMovimiento' => 'PAGO REMITO N° ' . $pagos->getRemito()->getId(),
                    'monto' => $pagos->getMonto(),
                    'modoPago' => $pagos->getModoPago(),
                    'saldoCuenta' => null,
                ];
            }
        }

        // Ordenar por fecha ASC
        usort($items, fn($a, $b) => $a['fechaCreacion'] <=> $b['fechaCreacion']);

        $saldo = 0;
        foreach ($items as $index => $item) {
            $saldo = $saldo + $item['monto'];
            $item['saldo'] = $saldo;
            $items[$index] = $item;
        }

        // Ordenar por fecha DESC
        usort($items, fn($a, $b) => $b['fechaCreacion'] <=> $a['fechaCreacion']);


        $html = $this->renderView('situacion_cliente/movimiento_todos_pdf.html.twig', array('entity' => $items, 'tipo_pdf' => "MOVIMIENTO"));
        $filename = "Movimientos.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

}