<?php

namespace App\Controller;


use App\Entity\Movimiento;
use App\Entity\Usuario;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/situacion_cliente")
 * @IsGranted("ROLE_SITUACION_CLIENTE")
 */
class SituacionClienteReporteController extends BaseController {

    /**
     * @Route("/", name="situacionclientereporte_index", methods={"GET"})
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
     * Print a Remito Entity.
     *
     * @Route("/imprimir-comprobante-movimiento/{id}", name="imprimir_comprobante_movimiento", methods={"GET"})
     */
    public function imprimirComprobanteMovimientoAction($id) {
        $em = $this->doctrine->getManager();

        /* @var $movimiento Movimiento */
        $movimiento = $em->getRepository("App\Entity\Movimiento")->find($id);

        if (!$movimiento) {
            $id = base64_decode($id);
            $movimiento = $em->getRepository("App\Entity\Movimiento")->find($id);
        }

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

        $remitos = $usuario->getRemitos();

        $items = [];

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


        $html = $this->renderView('situacion_cliente/movimiento_todos_pdf.html.twig', array('usuario' => $usuario, 'entity' => array_reverse($items), 'tipo_pdf' => "MOVIMIENTO"));
        $filename = "Movimientos.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-reporte-cc/{id}", name="imprimir_reporte_cc", methods={"GET"})
     */
    public function imprimirReporteCCAction($id): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        // Obtenemos ambas colecciones
        $movimientos = $usuario->getCuentaCorrienteUsuario()->getMovimientos();
        $remitos = $usuario->getRemitos();

        $resultado = [];

        // Adaptamos movimientos
        foreach ($movimientos as $movimiento) {
            if ($movimiento->getTipoMovimiento() != 'PAGO DE TRAMITE') {
                $resultado[] = [
                    'fechaCreacion' => $movimiento->getFechaCreacion(),
                    'tipoMovimiento' => 'INGRESO DINERO',
                    'monto' => $movimiento->getMonto(),
                    'modoPago' => $movimiento->getModoPago(),
                    'saldoCuenta' => $movimiento->getSaldoCuenta(),
                    'montoDeuda' => $movimiento->getMontoDeuda(),
                    'remito' => $movimiento->getRemito(),
                    'id' => $movimiento->getId(),
                ];
            }
        }

        // Adaptamos remitos
        foreach ($remitos as $remito) {
            $resultado[] = [
                'fechaCreacion' => $remito->getFechaCreacion(),
                'tipoMovimiento' => 'GENERÓ REMITO N° ' . $remito->getId(),
                'monto' => $remito->getTotalConDescuento(),
                'modoPago' => '-',
                'saldoCuenta' => $remito->getSaldoCuentaCorriente(),
                'montoDeuda' => $remito->getTotalDeuda(),
                'remito' =>  $remito,
                'id' => $remito->getId(),
            ];
        }

        foreach ($remitos as $remito) {
            foreach ($remito->getPagos() as $pagos) {
                $resultado[] = [
                    'fechaCreacion' => $pagos->getFechaCreacion(),
                    'tipoMovimiento' => 'PAGO REMITO N° ' . $pagos->getRemito()->getId(),
                    'monto' => $pagos->getMonto(),
                    'modoPago' => $pagos->getModoPago(),
                    'saldoCuenta' => $pagos->getSaldoCuentaCorriente(),
                    'montoDeuda' => $pagos->getTotalDeuda(),
                    'remito' => $pagos->getRemito(),
                    'id' => $pagos->getId(),
                ];
            }
        }

        // Ordenamos por fechaCreacion descendente
        usort($resultado, function($a, $b) {
            $cmp = $b['fechaCreacion'] <=> $a['fechaCreacion']; // descendente por fecha
            if ($cmp === 0) {
                return $b['id'] <=> $a['id']; // ascendente por id
            }
            return $cmp;
        });


        $html = $this->renderView('situacion_cliente/reporte.html.twig', array('usuario' => $usuario,'movimientos' => $resultado, 'tipo_pdf' => "MOVIMIENTO"));
        $filename = "Movimientos.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    /**
     * Print a Remito Entity.
     *
     * @Route("/imprimir-reporte-cc-from/{id}/{fecha}", name="imprimir_reporte_cc_from", methods={"GET"})
     */
    public function imprimirReporteCCFromAction($id, $fecha): Response
    {
        $em = $this->doctrine->getManager();

        /* @var $usuario Usuario */
        $usuario = $em->getRepository("App\Entity\Usuario")->find($id);

        if (!$usuario) {
            throw $this->createNotFoundException("No se puede encontrar la entidad.");
        }

        // Convertimos el parámetro recibido en DateTime
        try {
            $fechaFiltro = new \DateTime($fecha);
        } catch (\Exception $e) {
            throw $this->createNotFoundException("Formato de fecha inválido: " . $fecha);
        }

        // Obtenemos ambas colecciones
        $movimientos = $usuario->getCuentaCorrienteUsuario()->getMovimientos();
        $remitos = $usuario->getRemitos();

        $resultado = [];

        // Adaptamos movimientos
        foreach ($movimientos as $movimiento) {
            if ($movimiento->getTipoMovimiento() != 'PAGO DE TRAMITE'
                && $movimiento->getFechaCreacion() >= $fechaFiltro) {

                $resultado[] = [
                    'fechaCreacion' => $movimiento->getFechaCreacion(),
                    'tipoMovimiento' => 'INGRESO DINERO',
                    'monto' => $movimiento->getMonto(),
                    'modoPago' => $movimiento->getModoPago(),
                    'saldoCuenta' => $movimiento->getSaldoCuenta(),
                    'montoDeuda' => $movimiento->getMontoDeuda(),
                    'remito' => $movimiento->getRemito(),
                    'id' => $movimiento->getId(),
                ];
            }
        }

        foreach ($remitos as $remito) {
            $montoTotal = 0;
            $pagoReferencia = null; // guardo un pago para tomar datos

            foreach ($remito->getPagos() as $pago) {
                if ($pago->getFechaCreacion() >= $fechaFiltro) {
                    $montoTotal += $pago->getMonto();
                    $pagoReferencia = $pago; // me quedo con el último válido
                }
            }

            if ($pagoReferencia !== null) {
                $resultado[] = [
                    'fechaCreacion'  => $pagoReferencia->getFechaCreacion(),
                    'tipoMovimiento' => 'PAGO REMITO N° ' . $pagoReferencia->getRemito()->getId(),
                    'monto'          => $montoTotal, // 👈 total acumulado
                    'modoPago'       => $pagoReferencia->getModoPago(),
                    'saldoCuenta'    => $pagoReferencia->getSaldoCuentaCorriente(),
                    'montoDeuda'     => $pagoReferencia->getTotalDeuda(),
                    'remito'         => $pagoReferencia->getRemito(),
                    'id'             => $pagoReferencia->getId(),
                ];
            }
        }

        // Ordenamos por fechaCreacion descendente
        usort($resultado, function($a, $b) {
            $cmp = $b['fechaCreacion'] <=> $a['fechaCreacion']; // descendente por fecha
            if ($cmp === 0) {
                return $b['id'] <=> $a['id']; // ascendente por id
            }
            return $cmp;
        });

        $html = $this->renderView('situacion_cliente/reporte.html.twig', [
            'usuario' => $usuario,
            'movimientos' => $resultado,
            'tipo_pdf' => "MOVIMIENTO"
        ]);
        $filename = "Movimientos.pdf";
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }
}