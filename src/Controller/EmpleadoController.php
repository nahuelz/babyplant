<?php

namespace App\Controller;

use App\Entity\Adelanto;
use App\Entity\Constants\ConstanteTipoConceptoLiquidacion;
use App\Entity\Constants\ConstanteTipoConsulta;
use App\Entity\Empleado;
use App\Entity\Liquidacion;
use App\Entity\Vacaciones;
use App\Form\EmpleadoType;
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

        $liquidaciones = $entityManager->getRepository(Liquidacion::class)
            ->createQueryBuilder('l')
            ->where('l.empleado = :empleado')
            ->andWhere('l.padre IS NULL')
            ->andWhere('l.periodo LIKE :periodo')
            ->setParameter('empleado', $empleado)
            ->setParameter('periodo', $anioSeleccionado . '-%')
            ->orderBy('l.periodo', 'DESC')
            ->getQuery()
            ->getResult();

        $aniosDisponibles = $this->obtenerAniosDisponibles($empleado, $entityManager);

        $vacaciones = $entityManager->getRepository(Vacaciones::class)
            ->findOneBy([
                'empleado' => $empleado,
                'anio' => $anioSeleccionado,
            ]);

        $aniosVacaciones = $this->obtenerAniosVacaciones($empleado, $entityManager);

        $adelantos = $empleado->getAdelantos()->filter(function (Adelanto $a) {
            return $a->getFechaBaja() === null;
        });

        $resumenLiquidaciones = $this->calcularResumenLiquidaciones($liquidaciones);

        return $this->render('empleado/show/show.html.twig', [
            'empleado' => $empleado,
            'anioSeleccionado' => $anioSeleccionado,
            'anioActual' => $anioActual,
            'liquidaciones' => $liquidaciones,
            'resumenLiquidaciones' => $resumenLiquidaciones,
            'aniosDisponibles' => $aniosDisponibles,
            'vacaciones' => $vacaciones,
            'aniosVacaciones' => $aniosVacaciones,
            'adelantos' => $adelantos,
        ]);
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
        $diasTomados = (int) $request->request->get('diasTomados', 0);

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
        $vacaciones->setDiasTomados($diasTomados);

        $entityManager->persist($vacaciones);
        $entityManager->flush();

        $this->addFlash('success', 'Vacaciones actualizadas correctamente');

        return $this->redirectToRoute('app_empleado_show', ['id' => $empleado->getId()]);
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
        ];

        foreach ($liquidaciones as $liquidacion) {
            $resumen['cantidad']++;
            $resumen['neto'] = Decimal::add($resumen['neto'], $liquidacion->getSueldoNeto(), 2);
            $resumen['aPagar'] = Decimal::add($resumen['aPagar'], (string) $liquidacion->getTotalAPagar(), 2);

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
        $resultados = $entityManager->getRepository(Vacaciones::class)
            ->createQueryBuilder('v')
            ->select('v.anio')
            ->where('v.empleado = :empleado')
            ->setParameter('empleado', $empleado)
            ->orderBy('v.anio', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(function ($row) {
            return (int) $row['anio'];
        }, $resultados);
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

        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];

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
                    $nombreTipo = $tipo->getNombre();

                    if ($tipo->esDescuento()) {
                        if ($nombreTipo === 'Adelanto') {
                            $adelantos = Decimal::add($adelantos, $importe, 2);
                        }
                        continue;
                    }

                    switch ($nombreTipo) {
                        case 'Hora extra':
                            $horasExtras = Decimal::add($horasExtras, $importe, 2);
                            break;
                        case 'Feriado':
                            $feriados = Decimal::add($feriados, $importe, 2);
                            break;
                        case 'Guardia':
                            $guardias = Decimal::add($guardias, $importe, 2);
                            break;
                        case 'Otro':
                            $otros = Decimal::add($otros, $importe, 2);
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

    private function decimalToCsv(string $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
