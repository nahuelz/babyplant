<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteModoPago;
use App\Entity\Constants\ConstanteTipoMovimiento;
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
     * @Route("/adelanto_cc/new", name="adelanto_cc_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function movimientoNewAction(Request $request): Response
    {
        $movimiento = new Movimiento();

        $form = $this->createForm(MovimientoType::class, $movimiento, [
            'action' => $this->generateUrl('adelanto_cc_create'),
            'method' => 'POST',
        ]);

        return $this->render('situacion_cliente/cuenta_corriente_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'modal' => true,
            'token' => bin2hex(random_bytes(16)),
            'idCuentaCorrienteUsuario' => $request->request->get('idCuentaCorrienteUsuario'),
        ]);
    }


    /**
     * @Route("/adelanto_cc/create", name="adelanto_cc_create", methods={"POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function movimientoCreateAction(Request $request, MovimientoService $movimientoService): Response {
        try {
            $em = $this->doctrine->getManager();

            $cuentaCorrienteUsuario = $em
                ->getRepository(\App\Entity\CuentaCorrienteUsuario::class)
                ->find($request->request->get('idCuentaCorrienteUsuario'));

            if (!$cuentaCorrienteUsuario) {
                throw new \DomainException('Cuenta corriente no encontrada');
            }

            $movimiento = $movimientoService->crear([
                'token'                  => $request->request->get('token'),
                'monto'                  => $request->request->get('monto'),
                'modoPago'               => $request->request->get('modoPago'),
                'descripcion'            => $request->request->get('descripcion'),
                'tipoMovimiento'         => ConstanteTipoMovimiento::ADELANTO_CC,
                'cuentaCorrienteUsuario' => $cuentaCorrienteUsuario,
            ]);

            // Este dato no estaba en el service (detalle CC)
            $movimiento->setMontoDeuda($cuentaCorrienteUsuario->getPendiente());
            $em->flush();

            return $this->json([
                'message' => 'SALDO AGREGADO',
                'id' => $movimiento->getId(),
                'statusCode' => 200,

            ]);

        } catch (\DomainException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @Route("/adelanto_pedido/new", name="adelanto_pedido_new", methods={"GET","POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoNewAction(Request $request): Response
    {
        $movimiento = new Movimiento();
        $idCliente = $request->request->get('idCliente');

        $form = $this->createForm(MovimientoType::class, $movimiento, array(
            'action' => $this->generateUrl('adelanto_create'),
            'method' => 'POST',
            'idCliente' => $idCliente,
        ));

        return $this->render('situacion_cliente/movimiento_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'modal' => true,
            'token' => bin2hex(random_bytes(16))
        ]);
    }

    /**
     * @Route("/adelanto_pedido/create", name="adelanto_create", methods={"GET", "POST"})
     * @IsGranted("ROLE_SITUACION_CLIENTE")
     */
    public function adelantoCreateAction(
        Request $request,
        MovimientoService $movimientoService
    ): Response {
        try {
            $em = $this->doctrine->getManager();

            $pedido = $em->getRepository(Pedido::class)
                ->find($request->request->get('idPedido'));

            if (!$pedido) {
                throw new \DomainException('Pedido no encontrado');
            }

            $movimiento = $movimientoService->crear([
                'token'          => $request->request->get('token'),
                'monto'          => $request->request->get('monto'),
                'modoPago'       => $request->request->get('modoPago'),
                'descripcion'    => $request->request->get('descripcion'),
                'tipoMovimiento' => ConstanteTipoMovimiento::ADELANTO_PEDIDO,
                'pedido'         => $pedido,
            ]);

            return $this->json([
                'message' => 'ADELANTO AGREGADO',
                'id' => $movimiento->getId(),
                'statusCode' => 200,
            ]);

        } catch (\DomainException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @Route("/adelanto_reserva/new", name="adelanto_reserva_new", methods={"GET", "POST"})
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

        return $this->render('situacion_cliente/movimiento_reserva_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idReserva' => $reserva->getId(),
            'modal' => true,
            'esAjuste' => false,
            'token' => bin2hex(random_bytes(16)),
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

        return $this->render('situacion_cliente/movimiento_reserva_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $movimiento,
            'idReserva' => $reserva->getId(),
            'modal' => true,
            'esAjuste' => true,
            'token' => bin2hex(random_bytes(16)),
            'saldo' => $reserva->getAdelanto(),
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

            if (!isset($movimientoData['monto']) || $movimientoData['monto'] <= 0) {
                throw new \DomainException('El monto debe ser mayor a 0');
            }
            if ($tipoMovimiento === ConstanteTipoMovimiento::AJUSTE_RESERVA){
                if (!$reserva->puedeAjustarse($movimientoData['monto'])) {
                    throw new \DomainException('El monto ingresado supera el saldo de la reserva.');
                }

                $movimientoData['modoPago'] = ConstanteModoPago::AJUSTE;
            }

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

}