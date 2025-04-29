<?php

namespace App\Controller;

use App\Entity\Constants\ConstanteEstadoRemito;
use App\Entity\Constants\ConstanteModoPago;
use App\Entity\CuentaCorriente;
use App\Entity\EstadoRemito;
use App\Entity\EstadoRemitoHistorico;
use App\Entity\ModoPago;
use App\Entity\Movimiento;
use App\Entity\Pago;
use App\Entity\Remito;
use App\Entity\TipoMovimiento;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Mpdf\Mpdf;
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
    public function new(Request $request) {

        $em = $this->doctrine->getManager();
        $idRemito = $request->request->get('idRemito');
        $idCuentaCorriente = $request->request->get('idCuentaCorriente');
        $remito = $em->getRepository(Remito::class)->find($idRemito);
        $cuentaCorriente = $em->getRepository(CuentaCorriente::class)->find($idCuentaCorriente);

        $pago = new Pago();
        $pago->setMonto($remito->getPendiente());
        $pago->setRemito($remito);
        $form = $this->baseCreateCreateForm($pago);

        return $this->render('pago/pago_form.html.twig', [
            'form' => $form->createView(),
            'entity' => $pago,
            'remito' => $remito,
            'cuentaCorriente' => $cuentaCorriente,
            'modal' => true
        ]);
    }

    /**
     * @Route("/create", name="pago_create", methods={"GET","POST"})
     * @IsGranted("ROLE_PAGO")
     */
    public function createAction(Request $request) {
        $em = $this->doctrine->getManager();

        $monto = $request->request->get('monto');
        $modoPagoValue = $request->request->get('modoPago');
        $idRemito = $request->request->get('idRemito');
        $idCuentaCorriente = $request->request->get('idCuentaCorriente');
        $idPago = '';

        if ((isset($modoPagoValue) and $modoPagoValue !== '') and (isset($monto) and $monto !== '')) {
            $modoPago = $em->getRepository(ModoPago::class)->findOneByCodigoInterno($modoPagoValue);
            $remito = $em->getRepository(Remito::class)->find($idRemito);

            if ($monto == $remito->getPendiente()) {
                $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PAGO);
            } else {
                $estadoRemito = $em->getRepository(EstadoRemito::class)->findOneByCodigoInterno(ConstanteEstadoRemito::PAGO_PARCIAL);
            }

            $pago = new Pago();
            $pago->setMonto($monto);
            $pago->setModoPago($modoPago);
            $pago->setRemito($remito);
            $remito->addPago($pago);
            $em->persist($pago);
            $em->flush();

            if ($modoPagoValue == ConstanteModoPago::CUENTA_CORRIENTE){
                $tipoMovimiento = $em->getRepository(TipoMovimiento::class)->findOneByCodigoInterno(2); // 1 = PAGO DE REMITO
                $cuentaCorriente = $em->getRepository(CuentaCorriente::class)->find($idCuentaCorriente);

                $movimiento = new Movimiento();
                $movimiento->setMonto(-$monto);
                $movimiento->setModoPago($modoPago);
                $movimiento->setDescripcion('Pago Remito N° '.$remito->getId());
                $movimiento->setTipoMovimiento($tipoMovimiento);
                $movimiento->setRemito($remito);
                $cuentaCorriente->addMovimiento($movimiento);
                $movimiento->setSaldoCuenta($cuentaCorriente->getSaldo());
                $em->persist($movimiento);
            }
            $this->cambiarEstadoRemito($em, $remito, $estadoRemito, $pago);
            $em->flush();

            $idPago = $pago->getId();
        }

        $response = new Response();
        $response->setContent(json_encode(array(
            'message' => 'PAGO REGISTRADO',
            'statusCode' => 200,
            'statusText' => 'OK',
            'id' => $idPago
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
     * @param ObjectManager $em
     * @param Remito $remito
     * @param EstadoRemito $estadoRemito
     */
    private function cambiarEstadoRemito(ObjectManager $em, Remito $remito, EstadoRemito $estadoRemito, Pago $pago): void
    {
        $remito->setEstado($estadoRemito);
        $estadoRemitoHistorico = new EstadoRemitoHistorico();
        $estadoRemitoHistorico->setRemito($remito);
        $estadoRemitoHistorico->setFecha(new DateTime());
        $estadoRemitoHistorico->setEstado($estadoRemito);
        $estadoRemitoHistorico->setPago($pago);
        $estadoRemitoHistorico->setMotivo('Registro pago remito');
        $remito->addHistoricoEstado($estadoRemitoHistorico);

        $em->persist($estadoRemitoHistorico);
    }

    /**
     *
     * @Route("/imprimir-comprobante-pago/{id}", name="imprimir_comprobante_pago", methods={"GET"})
     */
    public function imprimirComprobantePagoAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $pago Pago */
        $pago = $em->getRepository("App\Entity\Pago")->find($id);

        if (!$pago) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('pago/comprobante_pdf.html.twig', array('entity' => $pago, 'website' => "http://192.168.0.182/babyplant/public/"));

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

        $mpdfService->SetBasePath($this->getParameter('MPDF_BASE_PATH'));

        $mpdfService->SetTitle($filename);

        $mpdfService->WriteHTML($html);

        $mpdfOutput = $mpdfService->Output($filename, $this->getPrintOutputType());

        return new Response($mpdfOutput);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-pago-ticket/{id}", name="imprimir_comprobante_pago_ticket", methods={"GET"})
     */
    public function imprimirComprobantePagoTicketAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $pago Pago */
        $pago = $em->getRepository("App\Entity\Pago")->find($id);

        if (!$pago) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('pago/comprobante_ticket_pdf.html.twig', array('entity' => $pago));

        $filename = 'pago.pdf';

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
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-pago-todos/{id}", name="imprimir_comprobante_pago_todos", methods={"GET"})
     */
    public function imprimirComprobantePagoTodosAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        $html = $this->renderView('pago/comprobante_todos_pdf.html.twig', array('entity' => $usuario, 'website' => "http://192.168.0.182/babyplant/public/"));

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
}