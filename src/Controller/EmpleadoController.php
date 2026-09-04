<?php

namespace App\Controller;

use App\Entity\Adelanto;
use App\Entity\Constants\ConstanteEstadoLiquidacion;
use App\Entity\Constants\ConstanteTipoConceptoLiquidacion;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Constants\ConstanteTipoModalidadPago;
use App\Entity\Empleado;
use App\Entity\Liquidacion;
use App\Entity\Prestamo;
use App\Entity\SolicitudVacaciones;
use App\Entity\Vacaciones;
use App\Form\EmpleadoType;
use App\Form\PrestamoType;
use App\Util\Decimal;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @Route("/empleado")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class EmpleadoController extends BaseController
{
    private const MESES = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
    ];

    #[Route('/', name: 'empleado_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('empleado/index.html.twig', [
            'select_boolean' => $this->selectService->getBooleanSelect(true)
        ]);
    }

    /**
     * @Route("/index_table/", name="empleado_table", methods={"GET|POST"})
     */
    public function indexTableAction(Request $request): Response {
        $entityTable = 'view_empleado';

        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('nombreCompleto', 'nombreCompleto');
        $rsm->addScalarResult('dni', 'dni');
        $rsm->addScalarResult('categoria', 'categoria');
        $rsm->addScalarResult('modalidadPago', 'modalidadPago');
        $rsm->addScalarResult('fechaIngreso', 'fechaIngreso');
        $rsm->addScalarResult('activo', 'activo');

        $columnDefinition = [
            ['field' => 'id', 'type' => '', 'searchable' => false, 'sortable' => false],
            ['field' => 'nombreCompleto', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'dni', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'categoria', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'modalidadPago', 'type' => 'string', 'searchable' => true, 'sortable' => true],
            ['field' => 'fechaIngreso', 'type' => 'date', 'searchable' => true, 'sortable' => true],
            ['field' => 'activo', 'type' => 'select', 'searchable' => true, 'sortable' => true],
            ['field' => 'acciones', 'type' => '', 'searchable' => false, 'sortable' => false]
        ];

        $renderPage = "empleado/index_table.html.twig";
        return parent::baseIndexTableAction($request, $columnDefinition, $entityTable, ConstanteTipoConsulta::VIEW, $rsm, $renderPage);
    }

    #[Route('/new', name: 'app_empleado_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $empleado = new Empleado();
        $form = $this->createForm(EmpleadoType::class, $empleado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($empleado);
            $entityManager->flush();

            return $this->redirectToRoute('empleado_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('empleado/new.html.twig', [
            'empleado' => $empleado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_empleado_show', methods: ['GET'])]
    public function show(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        $anioActual = (int) (new DateTime())->format('Y');
        $anioSeleccionado = (int) $request->query->get('anio', $anioActual);
        $mesSeleccionado = (int) $request->query->get('mes', 0);

        $qbLiquidaciones = $entityManager->getRepository(Liquidacion::class)
            ->createQueryBuilder('l')
            ->where('l.empleado = :empleado')
            ->andWhere('l.padre IS NULL')
            ->setParameter('empleado', $empleado)
            ->orderBy('l.periodo', 'DESC');

        if ($anioSeleccionado > 0 && $mesSeleccionado > 0) {
            $qbLiquidaciones
                ->andWhere('l.periodo = :periodo')
                ->setParameter('periodo', $anioSeleccionado . '-' . str_pad((string) $mesSeleccionado, 2, '0', STR_PAD_LEFT));
        } elseif ($anioSeleccionado > 0) {
            $qbLiquidaciones
                ->andWhere('l.periodo LIKE :periodo')
                ->setParameter('periodo', $anioSeleccionado . '-%');
        } elseif ($mesSeleccionado > 0) {
            $qbLiquidaciones
                ->andWhere('l.periodo LIKE :periodo')
                ->setParameter('periodo', '%-' . str_pad((string) $mesSeleccionado, 2, '0', STR_PAD_LEFT));
        }

        $liquidaciones = $qbLiquidaciones->getQuery()->getResult();

        $aniosDisponibles = $this->obtenerAniosDisponibles($empleado, $entityManager);
        $mesesDisponibles = self::MESES;
        $mesSeleccionadoNombre = $mesSeleccionado > 0
            ? (self::MESES[str_pad((string) $mesSeleccionado, 2, '0', STR_PAD_LEFT)] ?? null)
            : null;

        $anioVacacionesSeleccionado = $anioSeleccionado > 0 ? $anioSeleccionado : $anioActual;

        $vacaciones = $entityManager->getRepository(Vacaciones::class)
            ->findOneBy([
                'empleado' => $empleado,
                'anio' => $anioVacacionesSeleccionado,
            ]);

        $aniosVacaciones = $this->obtenerAniosVacaciones($empleado, $entityManager);

        $solicitudesVacaciones = $entityManager->getRepository(SolicitudVacaciones::class)
            ->createQueryBuilder('s')
            ->where('s.empleado = :empleado')
            ->andWhere('s.periodo = :periodo')
            ->setParameter('empleado', $empleado)
            ->setParameter('periodo', $anioVacacionesSeleccionado)
            ->orderBy('s.fechaDesde', 'DESC')
            ->getQuery()
            ->getResult();

        $diasTomadosVacaciones = $this->calcularDiasTomadosPeriodo($empleado, $anioVacacionesSeleccionado, $entityManager);
        $diasCorrespondientesVacaciones = $vacaciones ? (int) $vacaciones->getDiasCorrespondientes() : 0;
        $diasDisponiblesVacaciones = Decimal::sub((string) $diasCorrespondientesVacaciones, $diasTomadosVacaciones, 1);

        $adelantos = $empleado->getAdelantos()->filter(function (Adelanto $a) {
            return $a->getFechaBaja() === null;
        });

        $prestamos = $empleado->getPrestamos()->filter(function (Prestamo $p) {
            return $p->getFechaBaja() === null;
        });

        $formPrestamo = $this->createForm(PrestamoType::class, new Prestamo());

        $resumenLiquidaciones = $this->calcularResumenLiquidaciones($liquidaciones);

        $response = $this->render('empleado/show/show.html.twig', [
            'empleado' => $empleado,
            'anioSeleccionado' => $anioSeleccionado,
            'anioActual' => $anioActual,
            'mesSeleccionado' => $mesSeleccionado,
            'mesesDisponibles' => $mesesDisponibles,
            'mesSeleccionadoNombre' => $mesSeleccionadoNombre,
            'liquidaciones' => $liquidaciones,
            'resumenLiquidaciones' => $resumenLiquidaciones,
            'aniosDisponibles' => $aniosDisponibles,
            'vacaciones' => $vacaciones,
            'aniosVacaciones' => $aniosVacaciones,
            'anioVacacionesSeleccionado' => $anioVacacionesSeleccionado,
            'solicitudesVacaciones' => $solicitudesVacaciones,
            'diasTomadosVacaciones' => $diasTomadosVacaciones,
            'diasCorrespondientesVacaciones' => $diasCorrespondientesVacaciones,
            'diasDisponiblesVacaciones' => $diasDisponiblesVacaciones,
            'adelantos' => $adelantos,
            'prestamos' => $prestamos,
            'formPrestamo' => $formPrestamo->createView(),
        ]);

        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    #[Route('/{id}/reporte-anual/{anio}', name: 'app_empleado_reporte_anual', requirements: ['anio' => '\d{4}'], methods: ['GET'])]
    public function reporteAnual(Empleado $empleado, int $anio, EntityManagerInterface $entityManager): Response
    {
        $datos = $this->construirReporteAnual($empleado, $anio, $entityManager);

        return $this->render('empleado/reporte_anual.html.twig', [
            'empleado' => $empleado,
            'anio' => $anio,
            'reporte' => $datos['reporte'],
            'totales' => $datos['totales'],
        ]);
    }

    #[Route(
        '/{id}/reporte-anual/{anio}/excel',
        name: 'app_empleado_reporte_anual_excel',
        requirements: ['anio' => '\d{4}'],
        methods: ['GET']
    )]
    public function reporteAnualExcel(
        Empleado $empleado,
        int $anio,
        EntityManagerInterface $entityManager
    ): Response {
        $datos = $this->construirReporteAnual(
            $empleado,
            $anio,
            $entityManager
        );

        $reporte = $datos['reporte'];
        $totales = $datos['totales'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle((string) $anio);

        /*
         * ---------------------------------------------------------
         * 1. TÍTULO
         * ---------------------------------------------------------
         */

        $sheet->mergeCells('A1:I1');

        $sheet->setCellValue(
            'A1',
            strtoupper($empleado->getApellido() . ' ' . $empleado->getNombre())
        );

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 18,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(30);


        /*
         * ---------------------------------------------------------
         * 2. ENCABEZADOS
         * ---------------------------------------------------------
         */

        $headers = [
            'Mes',
            'BRUTO',
            'DEDUCCIONES',
            'NETO',
            '',
            'SUELDO/2',
            'ARREGLOS',
            'HORAS',
            'TOTAL',
        ];

        foreach ($headers as $col => $header) {
            $column = chr(65 + $col);

            $sheet->setCellValue(
                $column . '2',
                $header
            );
        }

        // Año
        $sheet->setCellValue('A2', $anio);

        $sheet->getStyle('A2:I2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'FFC000',
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '000000',
                    ],
                ],
            ],
        ]);

        // El año más grande, como en el Excel original
        $sheet->getStyle('A2')->getFont()->setSize(20);

        /*
         * ---------------------------------------------------------
         * 3. MESES
         * ---------------------------------------------------------
         */

        $fila = 3;

        foreach ($reporte as $mes) {

            $sheet->setCellValue('A' . $fila, $mes['nombre']);

            $sheet->setCellValue('B' . $fila, (float) $mes['bruto']);
            $sheet->setCellValue('C' . $fila, (float) $mes['deducciones']);
            $sheet->setCellValue('D' . $fila, (float) $mes['neto']);

            // Columna E queda vacía

            $sheet->setCellValue('F' . $fila, (float) $mes['sueldoMedio']);
            $sheet->setCellValue('G' . $fila, (float) $mes['arreglos']);
            $sheet->setCellValue('H' . $fila, (float) $mes['horasExtras']);

            /*
             * Total según el reporte de Claudia:
             *
             * Neto + Sueldo/2 + Arreglos + Horas
             *
             * Si posteriormente incorporás feriados,
             * guardias u otros, podemos agregarlos acá.
             */
            $sheet->setCellValue(
                'I' . $fila,
                '=D' . $fila . '+F' . $fila . '+G' . $fila . '+H' . $fila
            );

            $fila++;
        }

        /*
         * ---------------------------------------------------------
         * 4. FORMATO DE LOS DATOS
         * ---------------------------------------------------------
         */

        $ultimaFilaMeses = $fila - 1;

        $sheet->getStyle('A3:I' . $ultimaFilaMeses)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A3:A' . $ultimaFilaMeses)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID);

        $sheet->getStyle('A3:A' . $ultimaFilaMeses)
            ->getFill()
            ->getStartColor()
            ->setRGB('FFC000');

        $sheet->getStyle('A3:A' . $ultimaFilaMeses)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle('B3:I' . $ultimaFilaMeses)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        /*
         * Formato monetario
         */
        $sheet->getStyle('B3:I' . $ultimaFilaMeses)
            ->getNumberFormat()
            ->setFormatCode('#,##0.0');

        /*
         * ---------------------------------------------------------
         * 5. FILA TOTAL
         * ---------------------------------------------------------
         */

        $filaTotal = $ultimaFilaMeses + 1;

        $sheet->setCellValue('A' . $filaTotal, 'Total');

        $sheet->setCellValue('D' . $filaTotal, (float) $totales['neto']);
        $sheet->setCellValue('F' . $filaTotal, (float) $totales['sueldoMedio']);
        $sheet->setCellValue('G' . $filaTotal, (float) $totales['arreglos']);
        $sheet->setCellValue('H' . $filaTotal, (float) $totales['horasExtras']);

        $sheet->setCellValue(
            'I' . $filaTotal,
            '=D' . $filaTotal .
            '+F' . $filaTotal .
            '+G' . $filaTotal .
            '+H' . $filaTotal
        );

        $sheet->getStyle('A' . $filaTotal . ':I' . $filaTotal)
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => [
                            'rgb' => '000000',
                        ],
                    ],
                ],
            ]);

        $sheet->getStyle('A' . $filaTotal)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID);

        $sheet->getStyle('A' . $filaTotal)
            ->getFill()
            ->getStartColor()
            ->setRGB('FFC000');

        $sheet->getStyle(
            'D' . $filaTotal . ':I' . $filaTotal
        )
            ->getNumberFormat()
            ->setFormatCode('#,##0.0');


        /*
         * ---------------------------------------------------------
         * 6. ANCHOS
         * ---------------------------------------------------------
         */

        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(5);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(16);


        /*
         * ---------------------------------------------------------
         * 7. DESCARGA
         * ---------------------------------------------------------
         */

        $filename = sprintf(
            'reporte-%s-%d.xlsx',
            strtolower(
                str_replace(
                    ' ',
                    '-',
                    $empleado->getApellido() . '-' . $empleado->getNombre()
                )
            ),
            $anio
        );

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                'Content-Disposition' =>
                    'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/{id}/reporte-mensual/{periodo}', name: 'app_empleado_reporte_mensual', requirements: ['periodo' => '\d{4}-\d{2}'], methods: ['GET'])]
    public function reporteMensual(Empleado $empleado, string $periodo, EntityManagerInterface $entityManager): Response
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            throw $this->createNotFoundException('Período inválido.');
        }

        $semanas = $this->buildSemanasDelMes($periodo);
        $datos = $this->buildFilaMensual($empleado, $periodo, $semanas, $entityManager);

        return $this->render('empleado/reporte_mensual.html.twig', [
            'empleado' => $empleado,
            'periodo' => $periodo,
            'semanas' => $semanas,
            'fila' => $datos['fila'],
            'totales' => $datos['totales'],
        ]);
    }

    #[Route('/{id}/reporte-mensual/{periodo}/total-a-pagar/', name: 'app_empleado_reporte_mensual_total_a_pagar', requirements: ['periodo' => '\d{4}-\d{2}'], methods: ['GET'])]
    public function reporteMensualTotalAPagar(Empleado $empleado, string $periodo, EntityManagerInterface $entityManager): Response
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            throw $this->createNotFoundException('Período inválido.');
        }

        $semanas = $this->buildSemanasDelMes($periodo);
        $datos = $this->buildFilaMensual($empleado, $periodo, $semanas, $entityManager);

        return $this->json([
            'empleadoId' => $empleado->getId(),
            'periodo' => $periodo,
            'neto' => $datos['fila']['mensual']['neto'],
            'totalAPagar' => $datos['fila']['total'],
            'totalConceptos' => $datos['fila']['resumen']
                ? (string) $datos['fila']['resumen']->getTotalConceptos()
                : '0',
            'contribuciones' => $datos['fila']['mensual']['contribuciones'],
            'montoPagarEmpleado' => $datos['fila']['montoPagarEmpleado'],
        ]);
    }

    #[Route('/{id}/reporte-mensual/{periodo}/excel', name: 'app_empleado_reporte_mensual_excel', requirements: ['periodo' => '\d{4}-\d{2}'], methods: ['GET'])]
    public function reporteMensualExcel(Empleado $empleado, string $periodo, EntityManagerInterface $entityManager): Response
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            throw $this->createNotFoundException('Período inválido.');
        }

        $semanas = $this->buildSemanasDelMes($periodo);
        $datos = $this->buildFilaMensual($empleado, $periodo, $semanas, $entityManager);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->buildExcelSemanal($sheet, $datos['fila'], $semanas, $datos['totales'], $periodo);

        $filename = sprintf(
            'reporte-mensual-%s-%s.xlsx',
            $periodo,
            strtolower(str_replace(' ', '-', $empleado->getApellido() . '-' . $empleado->getNombre()))
        );

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return new Response(
            $content,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    #[Route('/{id}/edit', name: 'app_empleado_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmpleadoType::class, $empleado);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('empleado_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('empleado/new.html.twig', [
            'empleado' => $empleado,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_empleado_delete', methods: ['POST'])]
    public function delete(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$empleado->getId(), $request->request->get('_token'))) {
            $entityManager->remove($empleado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('empleado_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/activar_desactivar', name: 'app_empleado_activar_desactivar', methods: ['POST'])]
    public function empleadoActivarDesactivar(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('empleado_activar_desactivar_' . $empleado->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('empleado_index');
        }

        $empleado->setActivo(!$empleado->isActivo());
        $message = ($empleado->isActivo()) ? 'activó' : 'desactivó';
        $entityManager->flush();

        $this->addFlash('success', "Se " . $message . " correctamente al empleado");

        return $this->redirectToRoute('empleado_index');
    }

    /**
     * Carga/actualiza los días de vacaciones correspondientes al empleado para un año.
     *
     * @Route("/{id}/vacaciones", name="app_empleado_vacaciones", methods={"POST"})
     */
    public function guardarVacaciones(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        $anio = (int) $request->request->get('anio');
        $diasCorrespondientes = (int) $request->request->get('diasCorrespondientes');

        $vacaciones = $entityManager->getRepository(Vacaciones::class)->findOneBy([
            'empleado' => $empleado,
            'anio' => $anio,
        ]);

        if (!$vacaciones) {
            $vacaciones = new Vacaciones();
            $vacaciones->setEmpleado($empleado);
            $vacaciones->setAnio($anio);
        }

        $vacaciones->setDiasCorrespondientes($diasCorrespondientes);

        $entityManager->persist($vacaciones);
        $entityManager->flush();

        $this->addFlash('success', 'Vacaciones actualizadas correctamente');

        return $this->redirectToRoute('app_empleado_show', ['id' => $empleado->getId(), 'anio' => $anio]);
    }

    /**
     * Registra un período de vacaciones efectivamente tomado por el empleado.
     *
     * @Route("/{id}/vacaciones/solicitud", name="app_empleado_vacaciones_solicitud", methods={"POST"})
     */
    public function crearSolicitudVacaciones(Request $request, Empleado $empleado, EntityManagerInterface $entityManager): Response
    {
        $periodo = (int) $request->request->get('periodo');
        $fechaSolicitud = DateTime::createFromFormat('Y-m-d', (string) $request->request->get('fechaSolicitud'));
        $fechaDesde = DateTime::createFromFormat('Y-m-d', (string) $request->request->get('fechaDesde'));
        $fechaHasta = DateTime::createFromFormat('Y-m-d', (string) $request->request->get('fechaHasta'));
        $fechaReincorporacionStr = (string) $request->request->get('fechaReincorporacion');
        $fechaReincorporacion = $fechaReincorporacionStr !== ''
            ? DateTime::createFromFormat('Y-m-d', $fechaReincorporacionStr)
            : null;
        $cantidadDias = (string) $request->request->get('cantidadDias', '0');

        if ($periodo <= 0 || !$fechaSolicitud || !$fechaDesde || !$fechaHasta || Decimal::comp($cantidadDias, '0', 1) <= 0) {
            $this->addFlash('error', 'Complete todos los campos obligatorios de la solicitud de vacaciones.');

            return $this->redirectToRoute('app_empleado_show', ['id' => $empleado->getId(), 'anio' => $periodo]);
        }

        $vacaciones = $entityManager->getRepository(Vacaciones::class)->findOneBy([
            'empleado' => $empleado,
            'anio' => $periodo,
        ]);
        $diasCorrespondientes = $vacaciones ? (string) $vacaciones->getDiasCorrespondientes() : '0';

        $diasTomadosPrevios = $this->calcularDiasTomadosPeriodo($empleado, $periodo, $entityManager);
        $diasRestantesPeriodo = Decimal::sub($diasCorrespondientes, Decimal::add($diasTomadosPrevios, $cantidadDias, 1), 1);

        $solicitud = new SolicitudVacaciones();
        $solicitud->setEmpleado($empleado);
        $solicitud->setPeriodo($periodo);
        $solicitud->setFechaSolicitud($fechaSolicitud);
        $solicitud->setFechaDesde($fechaDesde);
        $solicitud->setFechaHasta($fechaHasta);
        $solicitud->setFechaReincorporacion($fechaReincorporacion);
        $solicitud->setCantidadDias($cantidadDias);
        $solicitud->setDiasRestantesPeriodo($diasRestantesPeriodo);

        $entityManager->persist($solicitud);
        $entityManager->flush();

        $this->addFlash('success', 'Solicitud de vacaciones registrada correctamente');

        return $this->redirectToRoute('app_empleado_show', ['id' => $empleado->getId(), 'anio' => $periodo]);
    }

    /**
     * Elimina un registro de vacaciones tomadas.
     *
     * @Route("/{id}/vacaciones/solicitud/{solicitud}", name="app_empleado_vacaciones_solicitud_eliminar", methods={"POST"})
     */
    public function eliminarSolicitudVacaciones(Empleado $empleado, SolicitudVacaciones $solicitud, EntityManagerInterface $entityManager): Response
    {
        $periodo = $solicitud->getPeriodo();

        $entityManager->remove($solicitud);
        $entityManager->flush();

        $this->addFlash('success', 'Registro de vacaciones eliminado correctamente');

        return $this->redirectToRoute('app_empleado_show', ['id' => $empleado->getId(), 'anio' => $periodo]);
    }

    /**
     * Imprime el comprobante de solicitud de vacaciones.
     *
     * @Route("/{id}/vacaciones/solicitud/{solicitud}/imprimir", name="app_empleado_vacaciones_solicitud_imprimir", methods={"GET"})
     */
    public function imprimirSolicitudVacaciones(Empleado $empleado, SolicitudVacaciones $solicitud): Response
    {
        $fechaSolicitud = $solicitud->getFechaSolicitud();
        $fechaDesde = $solicitud->getFechaDesde();
        $fechaHasta = $solicitud->getFechaHasta();
        $fechaReincorporacion = $solicitud->getFechaReincorporacion();

        $html = $this->renderView('empleado/solicitud_vacaciones_pdf.html.twig', [
            'empleado' => $empleado,
            'solicitud' => $solicitud,
            'diaFechaSolicitud' => (int) $fechaSolicitud->format('d'),
            'mesFechaSolicitud' => self::MESES[$fechaSolicitud->format('m')],
            'anioFechaSolicitud' => $fechaSolicitud->format('Y'),
            'diaDesde' => (int) $fechaDesde->format('d'),
            'mesDesde' => self::MESES[$fechaDesde->format('m')],
            'anioDesde' => $fechaDesde->format('Y'),
            'diaHasta' => (int) $fechaHasta->format('d'),
            'mesHasta' => self::MESES[$fechaHasta->format('m')],
            'anioHasta' => $fechaHasta->format('Y'),
            'fechaReincorporacion' => $fechaReincorporacion ? $fechaReincorporacion->format('d/m/Y') : '-',
        ]);

        $filename = 'SolicitudVacaciones.pdf';
        $basePath = $this->getParameter('MPDF_BASE_PATH');

        $mpdfOutput = $this->printService->printA4($basePath, $filename, $html);

        return new Response($mpdfOutput);
    }

    private function calcularDiasTomadosPeriodo(Empleado $empleado, int $periodo, EntityManagerInterface $entityManager): string
    {
        $total = $entityManager->getRepository(SolicitudVacaciones::class)
            ->createQueryBuilder('s')
            ->select('SUM(s.cantidadDias) as total')
            ->where('s.empleado = :empleado')
            ->andWhere('s.periodo = :periodo')
            ->setParameter('empleado', $empleado)
            ->setParameter('periodo', $periodo)
            ->getQuery()
            ->getSingleScalarResult();

        return $total !== null ? (string) $total : '0';
    }

    private function obtenerAniosDisponibles(Empleado $empleado, EntityManagerInterface $entityManager): array
    {
        $resultados = $entityManager->getRepository(Liquidacion::class)
            ->createQueryBuilder('l')
            ->select('SUBSTRING(l.periodo, 1, 4) as anio')
            ->where('l.empleado = :empleado')
            ->andWhere('l.padre IS NULL')
            ->setParameter('empleado', $empleado)
            ->groupBy('anio')
            ->orderBy('anio', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(function ($row) {
            return (int) $row['anio'];
        }, $resultados);
    }

    private function calcularResumenLiquidaciones(array $liquidaciones): array
    {
        $resumen = [
            'cantidad' => 0,
            'neto' => '0',
            'adicionales' => '0',
            'aPagar' => '0',
            'pagado' => '0',
        ];

        foreach ($liquidaciones as $liquidacion) {
            $resumen['cantidad']++;
            $resumen['neto'] = Decimal::add($resumen['neto'], $liquidacion->getSueldoNeto(), 2);

            $estadoCodigo = $liquidacion->getEstado() ? $liquidacion->getEstado()->getCodigoInterno() : null;
            if ($estadoCodigo !== ConstanteEstadoLiquidacion::PAGADA) {
                $resumen['aPagar'] = Decimal::add($resumen['aPagar'], (string) $liquidacion->getTotalAPagar(), 2);
            }

            foreach ($liquidacion->getPagos() as $pago) {
                $resumen['pagado'] = Decimal::add($resumen['pagado'], (string) $pago->getImporte(), 2);
            }

            $adicionalesLiquidacion = '0';
            foreach ($liquidacion->getConceptos() as $concepto) {
                $tipo = $concepto->getTipoConceptoLiquidacion();
                if ($tipo && !$tipo->esDescuento()) {
                    $adicionalesLiquidacion = Decimal::add($adicionalesLiquidacion, (string) $concepto->getImporte(), 2);
                }
            }
            $resumen['adicionales'] = Decimal::add($resumen['adicionales'], $adicionalesLiquidacion, 2);
        }

        return $resumen;
    }

    private function obtenerAniosVacaciones(Empleado $empleado, EntityManagerInterface $entityManager): array
    {
        $resultadosVacaciones = $entityManager->getRepository(Vacaciones::class)
            ->createQueryBuilder('v')
            ->select('v.anio')
            ->where('v.empleado = :empleado')
            ->setParameter('empleado', $empleado)
            ->getQuery()
            ->getResult();

        $resultadosSolicitudes = $entityManager->getRepository(SolicitudVacaciones::class)
            ->createQueryBuilder('s')
            ->select('s.periodo as anio')
            ->where('s.empleado = :empleado')
            ->setParameter('empleado', $empleado)
            ->getQuery()
            ->getResult();

        $anios = array_unique(array_map(function ($row) {
            return (int) $row['anio'];
        }, array_merge($resultadosVacaciones, $resultadosSolicitudes)));

        rsort($anios);

        return $anios;
    }

    private function construirReporteAnual(Empleado $empleado, int $anio, EntityManagerInterface $entityManager): array
    {
        $liquidaciones = $entityManager->getRepository(Liquidacion::class)
            ->createQueryBuilder('l')
            ->where('l.empleado = :empleado')
            ->andWhere('l.padre IS NULL')
            ->andWhere('l.periodo LIKE :periodo')
            ->setParameter('empleado', $empleado)
            ->setParameter('periodo', $anio . '-%')
            ->orderBy('l.periodo', 'ASC')
            ->getQuery()
            ->getResult();

        $meses = self::MESES;

        $reporte = [];
        $totales = [
            'bruto' => '0',
            'deducciones' => '0',
            'neto' => '0',
            'sueldoMedio' => '0',
            'arreglos' => '0',
            'horasExtras' => '0',
            'feriados' => '0',
            'guardias' => '0',
            'otros' => '0',
            'adelantos' => '0',
            'aPagar' => '0',
        ];

        foreach ($meses as $numero => $nombre) {
            $periodo = $anio . '-' . $numero;
            $liquidacionesMes = array_filter($liquidaciones, function (Liquidacion $l) use ($periodo) {
                return $l->getPeriodo() === $periodo;
            });

            $bruto = '0';
            $deducciones = '0';
            $neto = '0';
            $aPagar = '0';
            $horasExtras = '0';
            $feriados = '0';
            $guardias = '0';
            $otros = '0';
            $adelantos = '0';

            foreach ($liquidacionesMes as $liquidacion) {
                $bruto = Decimal::add($bruto, (string) $liquidacion->getSueldoBruto(), 2);
                $deducciones = Decimal::add($deducciones, (string) $liquidacion->getDeducciones(), 2);
                $neto = Decimal::add($neto, $liquidacion->getSueldoNeto(), 2);
                $aPagar = Decimal::add($aPagar, (string) $liquidacion->getTotalAPagar(), 2);

                foreach ($liquidacion->getConceptos() as $concepto) {
                    $tipo = $concepto->getTipoConceptoLiquidacion();
                    if (!$tipo) {
                        continue;
                    }

                    $importe = (string) $concepto->getImporte();
                    $signo = $tipo->esDescuento() ? '-1' : '1';
                    $importeSignado = Decimal::mul($importe, $signo, 2);

                    $nombreTipo = $tipo->getNombre();

                    if ($tipo->esDescuento()) {
                        if ($nombreTipo === 'Adelanto') {
                            $adelantos = Decimal::add($adelantos, $importeSignado, 2);
                        }
                        continue;
                    }

                    switch ($nombreTipo) {
                        case 'Hora extra':
                            $horasExtras = Decimal::add($horasExtras, $importeSignado, 2);
                            break;
                        case 'Feriado':
                            $feriados = Decimal::add($feriados, $importeSignado, 2);
                            break;
                        case 'Guardia':
                            $guardias = Decimal::add($guardias, $importeSignado, 2);
                            break;
                        case 'Otro':
                            $otros = Decimal::add($otros, $importeSignado, 2);
                            break;
                    }
                }
            }

            $sueldoMedio = '0';
            $arreglos = Decimal::sub($aPagar, $neto, 2);
            $arreglos = Decimal::sub($arreglos, $horasExtras, 2);
            $arreglos = Decimal::sub($arreglos, $feriados, 2);
            $arreglos = Decimal::sub($arreglos, $guardias, 2);
            $arreglos = Decimal::sub($arreglos, $otros, 2);
            $arreglos = Decimal::add($arreglos, $adelantos, 2);

            $reporte[$numero] = [
                'nombre' => $nombre,
                'bruto' => $bruto,
                'deducciones' => $deducciones,
                'neto' => $neto,
                'sueldoMedio' => $sueldoMedio,
                'arreglos' => $arreglos,
                'horasExtras' => $horasExtras,
                'feriados' => $feriados,
                'guardias' => $guardias,
                'otros' => $otros,
                'adelantos' => $adelantos,
                'aPagar' => $aPagar,
            ];

            $totales['bruto'] = Decimal::add($totales['bruto'], $bruto, 2);
            $totales['deducciones'] = Decimal::add($totales['deducciones'], $deducciones, 2);
            $totales['neto'] = Decimal::add($totales['neto'], $neto, 2);
            $totales['sueldoMedio'] = Decimal::add($totales['sueldoMedio'], $sueldoMedio, 2);
            $totales['arreglos'] = Decimal::add($totales['arreglos'], $arreglos, 2);
            $totales['horasExtras'] = Decimal::add($totales['horasExtras'], $horasExtras, 2);
            $totales['feriados'] = Decimal::add($totales['feriados'], $feriados, 2);
            $totales['guardias'] = Decimal::add($totales['guardias'], $guardias, 2);
            $totales['otros'] = Decimal::add($totales['otros'], $otros, 2);
            $totales['adelantos'] = Decimal::add($totales['adelantos'], $adelantos, 2);
            $totales['aPagar'] = Decimal::add($totales['aPagar'], $aPagar, 2);
        }

        return [
            'reporte' => $reporte,
            'totales' => $totales,
        ];
    }

    private function buildSemanasDelMes(string $periodo): array
    {
        $semanas = [];
        $inicio = new DateTime($periodo . '-01');
        $finMes = clone $inicio;
        $finMes->modify('last day of this month');

        $numero = 1;
        while ($inicio <= $finMes) {
            $fin = clone $inicio;
            $fin->modify('+6 days');

            if ($fin > $finMes) {
                $fin = clone $finMes;
            }

            $semanas[] = [
                'numero' => $numero,
                'inicio' => clone $inicio,
                'fin' => clone $fin,
                'label' => $inicio->format('d/m') . ' - ' . $fin->format('d/m'),
            ];

            $inicio = clone $fin;
            $inicio->modify('+1 day');
            $numero++;
        }

        return $semanas;
    }

    private function buildFilaMensual(Empleado $empleado, string $periodo, array $semanas, EntityManagerInterface $entityManager): array
    {
        $liquidaciones = $entityManager->getRepository(Liquidacion::class)->findBy([
            'empleado' => $empleado,
            'periodo' => $periodo,
        ]);

        $resumen = null;
        $detalles = [];
        foreach ($liquidaciones as $liquidacion) {
            if ($liquidacion->getPadre() === null) {
                $resumen = $liquidacion;
            } else {
                $detalles[$liquidacion->getFechaHasta()->format('Y-m-d')] = $liquidacion;
            }
        }

        $conceptosColumnas = [];
        $nombresVistos = [];
        $agregarConcepto = function ($concepto) use (&$conceptosColumnas, &$nombresVistos) {
            $tipo = $concepto->getTipoConceptoLiquidacion();
            if (!$tipo) {
                return;
            }

            $descripcion = (string) ($concepto->getDescripcion() ?? '');
            $nombre = $tipo->getNombre();

            $original = $nombre;
            $indice = 1;
            while (isset($nombresVistos[$nombre])) {
                $nombre = $original . ' (' . $indice . ')';
                $indice++;
            }
            $nombresVistos[$nombre] = true;

            $importe = (string) $concepto->getImporte();
            $signo = $tipo->esDescuento() ? '-1' : '1';
            $importeSignado = Decimal::mul($importe, $signo, 2);

            $conceptosColumnas[] = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'importe' => $importeSignado,
            ];
        };

        if ($resumen !== null) {
            foreach ($resumen->getConceptos() as $concepto) {
                $agregarConcepto($concepto);
            }
            foreach ($resumen->getDetallesSemanales() as $detalle) {
                foreach ($detalle->getConceptos() as $concepto) {
                    $agregarConcepto($concepto);
                }
            }
        }

        $totalAPagar = '0';
        $bruto = '0';
        $deducciones = '0';
        $neto = '0';
        $contribuciones = '0';
        $montoPagarEmpleado = '0';

        if ($resumen !== null) {
            $totalAPagar = (string) $resumen->getTotalAPagar();
            $bruto = (string) $resumen->getSueldoBruto();
            $deducciones = (string) $resumen->getDeducciones();
            $neto = (string) $resumen->getSueldoNeto();
            $contribuciones = (string) $resumen->getContribuciones();
            $montoPagarEmpleado = (string) $resumen->getMontoPagarEmpleado();
        }

        $esMensual = $empleado->getModalidadPago() && $empleado->getModalidadPago()->getCodigoInterno() == ConstanteTipoModalidadPago::MENSUAL;
        $esSemanal = $empleado->getModalidadPago() && $empleado->getModalidadPago()->getCodigoInterno() == ConstanteTipoModalidadPago::SEMANAL;

        $filaSemanas = [];
        $totalesPorSemana = [];

        foreach ($semanas as $semana) {
            $liquidacion = $detalles[$semana['fin']->format('Y-m-d')] ?? null;
            $valor = null;
            $valorRaw = null;
            $totalSemana = '0';

            if ($liquidacion) {
                $totalSemana = (string) $liquidacion->getTotalAPagar();

                if ($esSemanal) {
                    $netoSemanal = $liquidacion->getSueldoNeto();
                    $totalConceptos = (float) $liquidacion->getTotalConceptos();
                    if (abs($totalConceptos) > 0.001) {
                        $signo = $totalConceptos >= 0 ? '+' : '-';
                        $conceptos = $this->formatMoney(abs($totalConceptos));
                        $valor = $this->formatMoney($netoSemanal) . ' (' . $signo . $conceptos . ')';
                    } else {
                        $valor = $this->formatMoney($netoSemanal);
                    }
                    $valorRaw = $valor;
                } else {
                    $valor = $this->formatMoney($totalSemana);
                    $valorRaw = $totalSemana;
                }
            }

            $filaSemanas[] = [
                'numero' => $semana['numero'],
                'label' => $semana['label'],
                'liquidacion' => $liquidacion,
                'valor' => $valor,
                'valor_raw' => $valorRaw,
            ];

            $totalesPorSemana[] = $totalSemana;
        }

        $fila = [
            'empleado' => $empleado,
            'resumen' => $resumen,
            'modalidad' => $empleado->getModalidadPago() ? $empleado->getModalidadPago()->getNombre() : '-',
            'esMensual' => $esMensual,
            'semanas' => $filaSemanas,
            'mensual' => [
                'bruto' => $bruto,
                'deducciones' => $deducciones,
                'neto' => $neto,
                'contribuciones' => $contribuciones,
            ],
            'conceptos' => $conceptosColumnas,
            'total' => $totalAPagar,
            'montoPagarEmpleado' => $montoPagarEmpleado,
        ];

        $totales = [
            'semanas' => $totalesPorSemana,
            'mensual' => [
                'bruto' => $bruto,
                'deducciones' => $deducciones,
                'neto' => $neto,
                'contribuciones' => $contribuciones,
            ],
            'conceptos' => $conceptosColumnas,
            'total' => $totalAPagar,
            'montoPagarEmpleado' => $montoPagarEmpleado,
        ];

        return [
            'fila' => $fila,
            'totales' => $totales,
        ];
    }

    private function buildExcelSemanal(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $fila, array $semanas, array $totales, string $periodo): void
    {
        $conceptosColumnas = $fila['conceptos'];
        $totalConceptosColumnas = $totales['conceptos'];
        $totalGeneral = $totales['total'];
        $esMensual = $fila['esMensual'];

        $cantidadColumnas = count($conceptosColumnas) + 1;
        if ($esMensual) {
            $cantidadColumnas += 5;
        } else {
            $cantidadColumnas += count($semanas);
        }

        $ultimaColumna = 1 + $cantidadColumnas;

        $sheet->setCellValue('A1', 'Resumen de liquidaciones — ' . $periodo);
        $sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ultimaColumna) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $col = 2;
        if ($esMensual) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Bruto');
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Deducciones');
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Neto');
            $col++;
        } else {
            foreach ($semanas as $semana) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', "Sem " . $semana['numero'] . "\n" . $semana['label']);
                $col++;
            }
        }
        foreach ($conceptosColumnas as $concepto) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', $concepto['nombre']);
            $col++;
        }
        if ($esMensual) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Total a pagar');
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Contribuciones');
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Monto a pagar empleados');
        } else {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Total');
        }

        $headerRange = 'A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(2)->setRowHeight(35);

        $filaNum = 3;

        $col = 2;
        if ($esMensual) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $this->formatMoney($fila['mensual']['bruto']));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $this->formatMoney($fila['mensual']['deducciones']));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $this->formatMoney($fila['mensual']['neto']));
            $col++;
        } else {
            $esSemanal = $fila['empleado']->getModalidadPago() && $fila['empleado']->getModalidadPago()->getCodigoInterno() == ConstanteTipoModalidadPago::SEMANAL;
            foreach ($fila['semanas'] as $semana) {
                if ($semana['liquidacion']) {
                    if ($esSemanal) {
                        $sheet->setCellValueExplicit(
                            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum,
                            $semana['valor_raw'],
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    } else {
                        $valor = (float) $semana['valor_raw'];
                        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $valor);
                    }
                } else {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, '');
                }
                $col++;
            }
        }

        foreach ($conceptosColumnas as $concepto) {
            $valorCelda = $concepto['descripcion'] !== ''
                ? $concepto['descripcion'] . "\n" . $this->formatMoney($concepto['importe'])
                : $this->formatMoney($concepto['importe']);
            $sheet->setCellValueExplicit(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum,
                $valorCelda,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $col++;
        }

        if ($esMensual) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $this->formatMoney($fila['total']));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $this->formatMoney($fila['mensual']['contribuciones']));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $this->formatMoney($fila['montoPagarEmpleado']));
        } elseif ($fila['resumen']) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, $this->formatMoney($fila['total']));
        } else {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaNum, '');
        }

        $filaTotal = $filaNum + 1;
        $sheet->setCellValue('A' . $filaTotal, 'TOTAL');

        $col = 2;
        if ($esMensual) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, $this->formatMoney($totales['mensual']['bruto']));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, $this->formatMoney($totales['mensual']['deducciones']));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, $this->formatMoney($totales['mensual']['neto']));
            $col++;
        } else {
            foreach ($totales['semanas'] as $total) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, (float) $total);
                $col++;
            }
        }
        foreach ($totalConceptosColumnas as $concepto) {
            $sheet->setCellValue(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal,
                $this->formatMoney($concepto['importe'])
            );
            $col++;
        }
        if ($esMensual) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, $this->formatMoney($totalGeneral));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, $this->formatMoney($totales['mensual']['contribuciones']));
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, $this->formatMoney($totales['montoPagarEmpleado']));
        } else {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, $this->formatMoney($totalGeneral));
        }

        $totalRange = 'A' . $filaTotal . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal;
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E2E2');
        $sheet->getStyle($totalRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . ($filaTotal - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('B3:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range(1, $col) as $columnIndex) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function formatMoney($valor): string
    {
        $valor = (float) $valor;
        return '$' . number_format($valor, 2, ',', '.');
    }

    private function decimalToCsv(string $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
