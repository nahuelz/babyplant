<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoEntregaProducto;
use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\Constants\ConstanteModoPago;
use App\Entity\Constants\ConstanteTipoMovimiento;
use App\Entity\CuentaCorrienteUsuario;
use App\Entity\EstadoRemito;
use App\Entity\EstadoRemitoHistorico;
use App\Entity\ModoPago;
use App\Entity\Movimiento;
use App\Entity\Pago;
use App\Entity\Remito;
use App\Entity\TipoMovimiento;
use App\Entity\Usuario;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pago")
 * @IsGranted("ROLE_PAGO")
 */
class PagoController extends BaseController {

    /**
     * @Route("/", name="pago_index", methods={"GET"})
     * @Template("pago/index.html.twig")
     * @IsGranted("ROLE_PAGO")
     */
    public function index(): array
    {

        $clienteSelect = $this->getSelectService()->getClienteFilter();
        $estadoSelect = $this->getSelectService()->getEstadoSelect();


        return array(
            'clienteSelect' => $clienteSelect,
            'estadoSelect' => $estadoSelect,
            'page_title' => 'Pagos generados'
        );
    }

    /**
     * @Route("/new", name="pago_new", methods={"GET","POST"})
     * @IsGranted("ROLE_PAGO")
     */
    public function new(Request $request): Response
    {

        $em = $this->doctrine->getManager();
        $idRemito = $request->request->get('idRemito');
        $modoPago = $request->request->get('modoPago');
        $idCuentaCorrienteUsuario = $request->request->get('idCuentaCorrienteUsuario');
        $remito = $em->getRepository(Remito::class)->find($idRemito);
        $cuentaCorrienteUsuario = $em->getRepository(CuentaCorrienteUsuario::class)->find($idCuentaCorrienteUsuario);

        return $this->render('pago/pago.html.twig', [
            'remito' => $remito,
            'cuentaCorrienteUsuario' => $cuentaCorrienteUsuario,
            'modoPago' => $modoPago,
            'modal' => true
        ]);
    }

    /**
     * @Route("/adjudicar", name="pago_create", methods={"GET","POST"})
     * @IsGranted("ROLE_PAGO")
     */
    public function adjudicarAction(Request $request): Response
    {
        $em = $this->doctrine->getManager();
        $idRemito = $request->request->get('idRemito');
        $modoPago = $request->request->get('modoPago');

        if ((isset($idRemito)) and ($idRemito !== '')) {
            $remito = $em->getRepository(Remito::class)->find($idRemito);

            if ($modoPago == 'CC'){
                $this->adjudicarCC($em, $remito);
            }else{
                $this->adjudicarAdelanto($em, $remito);
            }

            if ($remito->getPendiente() == 0) {
                $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PAGO);
            } else {
                $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PAGO_PARCIAL);
            }

            $this->estadoService->cambiarEstadoRemito($remito, $estadoRemito, $modoPago);

            $em->flush();

            $response = new Response();
            $response->setContent(json_encode(array(
                'message' => 'PAGO REGISTRADO',
                'statusCode' => 200,
                'statusText' => 'OK'
            )));

            return $response;
        }
        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => 'NO SE ENCONTRO EL REMITO',
            'statusCode' => 303,
            'statusText' => 'ERROR'
        )));

        return $response;
    }

    /**
     * @Route("/{id}", name="pago_show", methods={"GET"})
     * @Template("pago/show.html.twig")
     */
    public function show($id): Array {
        return parent::baseShowAction($id);
    }

    /**
     * @Route("/{id}/edit", name="pago_edit", methods={"GET","POST"})
     * @Template("pago/new.html.twig")
     * @IsGranted("ROLE_PAGO")
     */
    public function edit($id): Array {
        return parent::baseEditAction($id);
    }

    /**
     * @Route("/{id}/actualizar", name="pago_update", methods={"PUT"})
     * @Template("pago/new.html.twig")
     * @IsGranted("ROLE_PAGO")
     */
    public function update(Request $request, $id) {
        return parent::baseUpdateAction($request, $id);
    }

    /**
     * @Route("/{id}/borrar", name="pago_delete", methods={"GET"})
     * @IsGranted("ROLE_PAGO")
     */
    public function delete($id) {
        return parent::baseDeleteAction($id);
    }

    /**
     *
     * @Route("/imprimir-comprobante-pago/{id}", name="imprimir_comprobante_pago", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirComprobantePagoAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $pago Pago */
        $pago = $em->getRepository("App\Entity\Pago")->find($id);

        if (!$pago) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('pago/comprobante_pdf.html.twig', array('entity' => $pago, 'website' => "http://192.168.0.182/babyplant/public/"));
        $filename = 'pago.pdf';
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        return new Response($this->printService->printA4($basePath,$filename, $html));
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-pago-ticket/{id}", name="imprimir_comprobante_pago_ticket", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirComprobantePagoTicketAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $pago Pago */
        $pago = $em->getRepository("App\Entity\Pago")->find($id);

        if (!$pago) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('pago/comprobante_ticket_pdf.html.twig', array('entity' => $pago));
        $filename = 'ticket_pago.pdf';
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        return new Response($this->printService->printTicket($basePath,$filename, $html));
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-pago-todos/{id}", name="imprimir_comprobante_pago_todos", methods={"GET"})
     * @throws MpdfException
     */
    public function imprimirComprobantePagoTodosAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $pagos = $em->createQueryBuilder()
            ->select('p')
            ->from(\App\Entity\Pago::class, 'p')
            ->join('p.remito', 'r')
            ->where('IDENTITY(r.cliente) = :idCliente')
            ->setParameter('idCliente', $id)
            ->orderBy('p.fechaCreacion', 'DESC')
            ->getQuery()
            ->getResult();

        $html = $this->renderView('pago/comprobante_todos_pdf.html.twig', array('entity' => $usuario, 'pagos'=>$pagos, 'website' => "https://dev.babyplant.com.ar"));
        $filename = 'pago.pdf';
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        return new Response($this->printService->printA4($basePath,$filename, $html));
    }

    protected function adjudicarPago($em, $remito, $modoPago, $tipoMovimiento, callable $getMontoDisponible, callable $getCuentaCorriente, string $motivo): bool
    {
        foreach ($remito->getEntregas() as $entrega) {
            foreach ($entrega->getEntregasProductos() as $entregaProducto) {
                if ($entregaProducto->getMontoPendiente() > 0){

                    $pedidoProducto = $entregaProducto->getPedidoProducto();
                    $montoDisponible = $getMontoDisponible($entregaProducto);

                    if ($montoDisponible > 0) {
                        $montoPago = min($montoDisponible, $entregaProducto->getMontoPendiente());

                        $movimiento = $this->crearMovimiento($montoPago, $modoPago, $remito, $tipoMovimiento);
                        $movimiento->setPedidoProducto($pedidoProducto);

                        $entregaProducto->descontarMontoPendiente($montoPago);

                        $cuentaCorriente = $getCuentaCorriente($entregaProducto);
                        $cuentaCorriente->addMovimiento($movimiento);

                        $movimiento->setSaldoCuenta($cuentaCorriente->getSaldo());

                        $pago = $this->crearPago($montoPago, $modoPago, $remito);

                        $estadoId = $entregaProducto->getMontoPendiente() == 0
                            ? ConstanteEstadoEntregaProducto::PAGO
                            : ConstanteEstadoEntregaProducto::PAGO_PARCIAL;

                        $estado = $em->getRepository("App\Entity\EstadoEntregaProducto")->find($estadoId);

                        $this->estadoService->cambiarEstadoEntregaProducto($entregaProducto, $estado, $motivo);

                        $em->persist($entregaProducto);
                        $em->persist($pago);
                        $em->persist($movimiento);
                        $em->flush();
                    }
                }
            }
        }

        return true;
    }

    protected function AdjudicarCC($em, $remito): bool
    {
        $modoPago = $em->getRepository("App\Entity\ModoPago")->find(ConstanteModoPago::CUENTA_CORRIENTE);
        $tipoMovimiento = $em->getRepository("App\Entity\TipoMovimiento")->find(ConstanteTipoMovimiento::PAGO_TRAMITE);

        return $this->adjudicarPago(
            $em,
            $remito,
            $modoPago,
            $tipoMovimiento,
            fn($entregaProducto) => $remito->getCliente()->getCuentaCorrienteUsuario()->getSaldo(),
            fn($entregaProducto) => $remito->getCliente()->getCuentaCorrienteUsuario(),
            'CUENTA CORRIENTE'
        );
    }

    protected function adjudicarAdelanto($em, $remito): bool
    {
        $modoPago = $em->getRepository("App\Entity\ModoPago")->find(ConstanteModoPago::ADELANTO);
        $tipoMovimiento = $em->getRepository("App\Entity\TipoMovimiento")->find(ConstanteTipoMovimiento::PAGO_TRAMITE);

        return $this->adjudicarPago(
            $em,
            $remito,
            $modoPago,
            $tipoMovimiento,
            fn($entregaProducto) => $entregaProducto->getPedidoProducto()->getAdelanto(),
            fn($entregaProducto) => $entregaProducto->getPedidoProducto()->getCuentaCorrientePedido(),
            'ADELANTO'
        );
    }

    protected function crearMovimiento($monto, $modoPago, $remito, $tipoMovimiento): Movimiento
    {
        $movimiento = new Movimiento();
        $movimiento->setMonto(-$monto);
        $movimiento->setModoPago($modoPago);
        $movimiento->setDescripcion('Pago Remito N° '.$remito->getId());
        $movimiento->setTipoMovimiento($tipoMovimiento);
        $movimiento->setRemito($remito);

        return $movimiento;
    }

    private function crearPago($monto, $modoPago, $remito): Pago
    {
        $pago = new Pago();
        $pago->setMonto($monto);
        $pago->setModoPago($modoPago);
        $pago->setRemito($remito);
        $montoPendiente = $remito->getPendiente() - $monto;
        $pago->setMontoPendiente($montoPendiente);
        $pago->setSaldoCuentaCorriente($remito->getCliente()->getCuentaCorrienteUsuario()->getSaldo());
        $totalDeuda = $remito->getCliente()->getCuentaCorrienteUsuario()->getPendiente() - $monto;
        $pago->setTotalDeuda($totalDeuda);
        $remito->addPago($pago);

        return $pago;
    }
}