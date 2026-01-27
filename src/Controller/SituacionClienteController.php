<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteModoPago;
use App\Entity\Constants\ConstanteTipoMovimiento;
use App\Entity\CuentaCorrientePedido;
use App\Entity\CuentaCorrienteUsuario;
use App\Entity\Movimiento;
use App\Entity\Pedido;
use App\Entity\Reserva;
use App\Form\MovimientoType;
use App\Service\MovimientoService;
use App\Service\SituacionClienteService;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     * @Route("/index_table/", name="situacion_cliente_table", methods={"GET", "POST"})
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
        $rsm->addScalarResult('saldoAFavor', 'saldoAFavor');
        $rsm->addScalarResult('deuda', 'deuda');

        $nativeQuery = $em->createNativeQuery('call sp_index_situacion_cliente(?)', $rsm);

        $nativeQuery->setParameter(1, $cliente);

        $entities = $nativeQuery->getResult();

        return $this->render('situacion_cliente/index_table.html.twig', array('entities' => $entities));
    }

    /**
     * @Route("/{id}", name="situacioncliente_show", methods={"GET"})
     * @Template("situacion_cliente/show.html.twig")
     */
    public function show(int $id, SituacionClienteService $situacionClienteService): array
    {
        $entity = $situacionClienteService->cargarUsuarioCompleto($id);

        if (!$entity) {
            throw $this->createNotFoundException("No se puede encontrar el usuario.");
        }

        $situacionClienteService->crearCuentasCorrientesFaltantes($entity);

        $pagos = $situacionClienteService->obtenerPagos($id);

        $breadcrumbs = $this->getShowBaseBreadcrumbs($entity);

        $parametros = array(
            'entity' => $entity,
            'pagos' => $pagos,
            'breadcrumbs' => $breadcrumbs,
            'page_title' => 'Detalle ' . $this->getEntityRenderName()
        );

        return array_merge($parametros, $this->getExtraParametersShowAction($entity));
    }

    /**
     * @Route("/adelanto_reserva/new", name="adelanto_reserva_new", methods={"GET"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoReservaNewAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $reserva = $em->getRepository(Reserva::class)
            ->find($request->query->get('idReserva'));

        if (!$reserva) {
            throw $this->createNotFoundException('Reserva no encontrada');
        }

        $movimiento = new Movimiento();
        $movimiento->setReserva($reserva);

        $form = $this->createForm(MovimientoType::class, $movimiento, [
            'action' => $this->generateUrl('adelanto_reserva_create'),
            'method' => 'POST',
            'idCliente' => $reserva->getCliente()->getId(),
        ]);

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idReserva' => $reserva->getId(),
            'idCuentaCorrienteUsuario' => null,
            'idCuentaCorrientePedido' => null,
            'modal' => true,
            'esAjuste' => false,
            'token' => bin2hex(random_bytes(16)),
            'mostrarSaldo' => true,
            'saldo' => $reserva->getAdelanto(),
        ]);
    }

    /**
     * @Route("/ajuste_reserva/new", name="ajuste_reserva_new", methods={"GET"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function ajusteReservaNewAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $idReserva = $request->query->get('idReserva');
        $reserva = $em->getRepository(Reserva::class)->find($idReserva);

        if (!$reserva) {
            throw $this->createNotFoundException('Reserva no encontrada');
        }

        $movimiento = new Movimiento();
        $movimiento->setReserva($reserva);

        $form = $this->createForm(MovimientoType::class, $movimiento, [
            'action' => $this->generateUrl('ajuste_reserva_create'),
            'method' => 'POST',
            'idCliente' => $reserva->getCliente()->getId(),
        ]);

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idReserva' => $reserva->getId(),
            'idCuentaCorrienteUsuario' => null,
            'idCuentaCorrientePedido' => null,
            'modal' => true,
            'esAjuste' => true,
            'token' => bin2hex(random_bytes(16)),
            'saldo' => $reserva->getAdelanto(),
            'mostrarSaldo' => true,
        ]);
    }


    /**
     * @Route("/adelanto_reserva/create", name="adelanto_reserva_create", methods={"POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoReservaCreateAction(Request $request, MovimientoService $movimientoService): Response {
        return $this->crearMovimientoReserva($request, $movimientoService, ConstanteTipoMovimiento::ADELANTO_RESERVA
        );
    }

    /**
     * @Route("/ajuste_reserva/create", name="ajuste_reserva_create", methods={"POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function ajusteReservaCreateAction(Request $request, MovimientoService $movimientoService): Response {
        return $this->crearMovimientoReserva($request, $movimientoService, ConstanteTipoMovimiento::AJUSTE_RESERVA);
    }


    private function crearMovimientoReserva(Request $request, MovimientoService $movimientoService, int $tipoMovimiento): Response {
        try {
            $em = $this->doctrine->getManager();

            $reserva = $em->getRepository(Reserva::class)->find($request->request->get('idReserva'));

            if (!$reserva) {
                throw new \DomainException('Reserva no encontrada');
            }

            $movimientoData = $request->request->get('movimiento');

            $movimiento = $movimientoService->crear([
                'monto'          => $movimientoData['monto'],
                'modoPago'       => $movimientoData['modoPago'] ?? null,
                'descripcion'    => $movimientoData['descripcion'] ?? null,
                'token'          => $request->request->get('token'),
                'tipoMovimiento' => $tipoMovimiento,
                'reserva'        => $reserva,
            ]);

            return $this->json([
                'message' => 'OPERACION REALIZADA',
                'id' => $movimiento->getId(),
                'statusCode' => 200,
            ]);

        } catch (\DomainException $e) {
            return $this->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    /**
     * @Route("/adelanto_cc/new", name="adelanto_cc_new", methods={"GET"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoCCNewAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $cuentaCorrienteUsuario = $em->getRepository(CuentaCorrienteUsuario::class)->find($request->query->get('idCuentaCorrienteUsuario'));

        if (!$cuentaCorrienteUsuario) {
            throw $this->createNotFoundException('Cuenta Corriente no encontrada');
        }

        $movimiento = new Movimiento();
        $movimiento->setCuentaCorrienteUsuario($cuentaCorrienteUsuario);

        $form = $this->createForm(MovimientoType::class, $movimiento, [
            'action' => $this->generateUrl('adelanto_reserva_create'),
            'method' => 'POST',
            'idCliente' => $cuentaCorrienteUsuario->getCliente()->getId(),
        ]);

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idCuentaCorrienteUsuario' => $cuentaCorrienteUsuario->getId(),
            'idCuentaCorrientePedido' => null,
            'idReserva' => null,
            'modal' => true,
            'esAjuste' => false,
            'token' => bin2hex(random_bytes(16)),
            'saldo' => $cuentaCorrienteUsuario->getSaldo(),
            'mostrarSaldo' => true,
        ]);
    }

    /**
     * @Route("/ajuste_cc/new", name="ajuste_cc_new", methods={"GET"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function ajusteCCNewAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $idCuentaCorrienteUsuario = $request->query->get('idCuentaCorrienteUsuario');
        $cuentaCorrienteUsuario = $em->getRepository(CuentaCorrienteUsuario::class)->find($idCuentaCorrienteUsuario);

        if (!$cuentaCorrienteUsuario) {
            throw $this->createNotFoundException('Cuenta corriente no encontrada');
        }

        $movimiento = new Movimiento();
        $movimiento->setCuentaCorrienteUsuario($cuentaCorrienteUsuario);

        $form = $this->createForm(MovimientoType::class, $movimiento, [
            'action' => $this->generateUrl('ajuste_reserva_create'),
            'method' => 'POST',
            'idCliente' => $cuentaCorrienteUsuario->getCliente()->getId(),
        ]);

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idCuentaCorrienteUsuario' => $cuentaCorrienteUsuario->getId(),
            'idCuentaCorrientePedido' => null,
            'idReserva' => null,
            'modal' => true,
            'esAjuste' => true,
            'token' => bin2hex(random_bytes(16)),
            'saldo' => $cuentaCorrienteUsuario->getSaldo(),
            'mostrarSaldo' => true,
        ]);
    }


    /**
     * @Route("/adelanto_cc/create", name="adelanto_cc_create", methods={"POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoCCCreateAction(Request $request, MovimientoService $movimientoService): Response {
        return $this->crearMovimientoCC($request, $movimientoService, ConstanteTipoMovimiento::ADELANTO_CC
        );
    }

    /**
     * @Route("/ajuste_cc/create", name="ajuste_cc_create", methods={"POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function ajusteCCCreateAction(Request $request, MovimientoService $movimientoService): Response {
        return $this->crearMovimientoCC($request, $movimientoService, ConstanteTipoMovimiento::AJUSTE_CC);
    }


    private function crearMovimientoCC(Request $request, MovimientoService $movimientoService, int $tipoMovimiento): Response {
        try {
            $em = $this->doctrine->getManager();

            $cuentaCorrienteUsuario = $em->getRepository(CuentaCorrienteUsuario::class)->find($request->request->get('idCuentaCorrienteUsuario'));

            if (!$cuentaCorrienteUsuario) {
                throw new \DomainException('Cuenta Corriente no encontrada');
            }

            $movimientoData = $request->request->get('movimiento');

            $movimiento = $movimientoService->crear([
                'monto'                     => $movimientoData['monto'],
                'modoPago'                  => $movimientoData['modoPago'] ?? null,
                'descripcion'               => $movimientoData['descripcion'] ?? null,
                'token'                     => $request->request->get('token'),
                'tipoMovimiento'            => $tipoMovimiento,
                'cuentaCorrienteUsuario'    => $cuentaCorrienteUsuario,
            ]);

            return $this->json([
                'message' => 'OPERACION REALIZADA',
                'id' => $movimiento->getId(),
                'statusCode' => 200,
            ]);

        } catch (\DomainException $e) {
            return $this->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }


    /**
     * @Route("/adelanto_pedido/new", name="adelanto_pedido_new", methods={"GET"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoPedidoNewAction(Request $request): Response
    {
        $movimiento = new Movimiento();;

        $form = $this->createForm(MovimientoType::class, $movimiento, [
            'action' => $this->generateUrl('adelanto_pedido_create'),
            'method' => 'POST',
            'idCliente' => $request->query->get('idCliente'),
        ]);

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idCuentaCorrienteUsuario' => null,
            'idCuentaCorrientePedido' => true,
            'idReserva' => null,
            'modal' => true,
            'esAjuste' => false,
            'mostrarSaldo' => false,
            'saldo' => false,
            'token' => bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * @Route("/ajuste_pedido/new", name="ajuste_pedido_new", methods={"GET"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function ajustePedidoNewAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();

        $idCuentaCorrientePedido = $request->query->get('idCuentaCorrienteUsuario');
        $cuentaCorrientePedido = $em->getRepository(CuentaCorrientePedido::class)->find($idCuentaCorrientePedido);

        if (!$cuentaCorrientePedido) {
            throw $this->createNotFoundException('Cuenta corriente no encontrada');
        }

        $movimiento = new Movimiento();
        $movimiento->setCuentaCorrienteUsuario($cuentaCorrientePedido);

        $form = $this->createForm(MovimientoType::class, $movimiento, [
            'action' => $this->generateUrl('ajuste_reserva_create'),
            'method' => 'POST',
            'idCliente' => $cuentaCorrientePedido->getCliente()->getId(),
        ]);

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idCuentaCorrienteUsuario' => null,
            'idCuentaCorrientePedido' => $cuentaCorrientePedido->getId(),
            'idReserva' => null,
            'modal' => true,
            'esAjuste' => true,
            'token' => bin2hex(random_bytes(16)),
            'saldo' => $cuentaCorrientePedido->getSaldo(),
            'mostrarSaldo' => true,
        ]);
    }


    /**
     * @Route("/adelanto_pedido/create", name="adelanto_pedido_create", methods={"POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoPedidoCreateAction(Request $request, MovimientoService $movimientoService): Response {
        return $this->crearMovimientoPedido($request, $movimientoService, ConstanteTipoMovimiento::ADELANTO_PEDIDO
        );
    }

    /**
     * @Route("/ajuste_pedido/create", name="ajuste_pedido_create", methods={"POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function ajustePedidoCreateAction(Request $request, MovimientoService $movimientoService): Response {
        return $this->crearMovimientoPedido($request, $movimientoService, ConstanteTipoMovimiento::AJUSTE_PEDIDO);
    }


    private function crearMovimientoPedido(Request $request, MovimientoService $movimientoService, int $tipoMovimiento): Response {
        try {
            $em = $this->doctrine->getManager();

            $movimientoData = $request->request->get('movimiento');

            $idPedido = $movimientoData['pedido'];

            $pedido = $em->getRepository(Pedido::class)->find($idPedido);

            if (!$pedido) {
                throw new \DomainException('Pedido no encontrado');
            }

            $cuentaCorrientePedido = $pedido->getCuentaCorrientePedido();

            $movimiento = $movimientoService->crear([
                'monto'                         => $movimientoData['monto'],
                'modoPago'                      => $movimientoData['modoPago'] ?? null,
                'descripcion'                   => $movimientoData['descripcion'] ?? null,
                'token'                         => $request->request->get('token'),
                'tipoMovimiento'                => $tipoMovimiento,
                'cuentaCorrienteUsuarioPedido'  => $cuentaCorrientePedido,
            ]);

            return $this->json([
                'message' => 'OPERACION REALIZADA',
                'id' => $movimiento->getId(),
                'statusCode' => 200,
            ]);

        } catch (\DomainException $e) {
            return $this->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}