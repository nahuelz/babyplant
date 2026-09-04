<?php

namespace App\Controller;

use App\Entity\ConceptoLiquidacion;
use App\Entity\Constants\ConstanteEstadoLiquidacion;
use App\Entity\Constants\ConstanteTipoModalidadPago;
use App\Entity\Empleado;
use App\Entity\EstadoLiquidacion;
use App\Entity\Liquidacion;
use App\Entity\PagoEmpleado;
use App\Entity\TipoConceptoLiquidacion;
use App\Entity\TipoModalidadPago;
use App\Form\LiquidacionType;
use App\Form\PagoEmpleadoType;
use App\Service\LiquidacionService;
use App\Util\Decimal;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/liquidacion")
 * @IsGranted("ROLE_EMPLEADO_CRUD")
 */
class LiquidacionController extends BaseController
{
    /**
     * @Route("/", name="liquidacion_index", methods={"GET"})
     */
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vista = $request->query->get('vista', 'semanal');
        if (!in_array($vista, ['semanal', 'mensual', 'anual'], true)) {
            $vista = 'semanal';
        }

        $periodo = $request->query->get('periodo', (new DateTime())->format('Y-m'));
        if ($vista === 'anual') {
            $periodo = preg_match('/^\d{4}/', $periodo) ? substr($periodo, 0, 4) : (new DateTime())->format('Y');
        } else {
            if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                if (preg_match('/^\d{4}$/', $periodo)) {
                    $periodo = $periodo . '-' . (new DateTime())->format('m');
                } else {
                    $periodo = (new DateTime())->format('Y-m');
                }
            }
        }

        $empleadoId = $request->query->get('empleado');
        $modalidadId = $request->query->get('modalidad');

        $semanas = $vista === 'anual' ? [] : $this->buildSemanasDelMes($periodo);
        $empleadosActivos = $entityManager->getRepository(Empleado::class)->findBy(
            ['activo' => true],
            ['apellido' => 'ASC', 'nombre' => 'ASC']
        );

        $modalidades = $entityManager->getRepository(TipoModalidadPago::class)
            ->findBy([], ['nombre' => 'ASC']);

        $empleados = $empleadosActivos;
        if ($empleadoId) {
            $empleadoFiltrado = $entityManager->getRepository(Empleado::class)->find($empleadoId);
            if ($empleadoFiltrado && $empleadoFiltrado->isActivo()) {
                $empleados = [$empleadoFiltrado];
            }
        }

        if ($modalidadId) {
            $empleados = array_filter($empleados, function (Empleado $empleado) use ($modalidadId) {
                $modalidad = $empleado->getModalidadPago();
                return $modalidad && $modalidad->getId() == $modalidadId;
            });
        }

        $grid = [];
        $totalesPorSemana = [];
        $totalGeneral = '0';
        $totalesMensuales = [];
        $gridAnual = [];
        $mesesNombres = [];
        $totalesMeses = [];
        $totalGeneralAnual = '0';

        if ($vista === 'anual') {
            $datosAnuales = $this->buildGridAnual($empleados, $periodo, $entityManager);
            $gridAnual = $datosAnuales['grid'];
            $mesesNombres = $datosAnuales['meses_nombres'];
            $totalesMeses = $datosAnuales['totales_meses'];
            $totalGeneralAnual = $datosAnuales['total_general_anual'];
        } else {
            foreach ($empleados as $empleado) {
                $grid[] = $this->buildFila($empleado, $periodo, $semanas, $entityManager);
            }

            $totalesPorSemana = array_fill(0, count($semanas), '0');
            $totalesMensuales = [
                'bruto' => '0',
                'deducciones' => '0',
                'contribuciones' => '0',
                'neto' => '0',
                'conceptos' => '0',
                'total' => '0',
            ];

            foreach ($grid as $fila) {
                foreach ($fila['semanas'] as $index => $semana) {
                    if ($semana['liquidacion']) {
                        $totalesPorSemana[$index] = Decimal::add($totalesPorSemana[$index], (string) $semana['liquidacion']->getTotalAPagar(), 2);
                    }
                }

                if ($fila['resumen']) {
                    $totalGeneral = Decimal::add($totalGeneral, (string) $fila['resumen']->getTotalAPagar(), 2);
                    $totalesMensuales['bruto'] = Decimal::add($totalesMensuales['bruto'], (string) $fila['resumen']->getSueldoBruto(), 2);
                    $totalesMensuales['deducciones'] = Decimal::add($totalesMensuales['deducciones'], (string) $fila['resumen']->getDeducciones(), 2);
                    $totalesMensuales['contribuciones'] = Decimal::add($totalesMensuales['contribuciones'], (string) $fila['resumen']->getContribuciones(), 2);
                    $totalesMensuales['neto'] = Decimal::add($totalesMensuales['neto'], (string) $fila['resumen']->getSueldoNeto(), 2);
                    $conceptosFila = Decimal::add((string) $fila['resumen']->getTotalConceptosSemanas(), (string) $fila['resumen']->getTotalConceptos(), 2);
                    $totalesMensuales['conceptos'] = Decimal::add($totalesMensuales['conceptos'], $conceptosFila, 2);
                    $totalesMensuales['total'] = Decimal::add($totalesMensuales['total'], (string) $fila['resumen']->getTotalAPagar(), 2);
                }
            }
        }

        return $this->render('liquidacion/index.html.twig', [
            'periodo' => $periodo,
            'empleadoId' => $empleadoId,
            'modalidadSeleccionada' => $modalidadId,
            'vista' => $vista,
            'modalidades' => $modalidades,
            'empleadosActivos' => $empleadosActivos,
            'semanas' => $semanas,
            'grid' => $grid,
            'totales_semanas' => $totalesPorSemana,
            'total_general' => $totalGeneral,
            'totales_mensuales' => $totalesMensuales,
            'grid_anual' => $gridAnual,
            'meses_nombres' => $mesesNombres,
            'totales_meses' => $totalesMeses,
            'total_general_anual' => $totalGeneralAnual,
        ]);
    }

    /**
     * @Route("/excel", name="liquidacion_index_excel", methods={"GET"})
     */
    public function exportarExcel(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vista = $request->query->get('vista', 'semanal');
        if (!in_array($vista, ['semanal', 'mensual', 'anual'], true)) {
            $vista = 'semanal';
        }

        $periodo = $request->query->get('periodo', (new DateTime())->format('Y-m'));
        if ($vista === 'anual') {
            $periodo = preg_match('/^\d{4}/', $periodo) ? substr($periodo, 0, 4) : (new DateTime())->format('Y');
        } else {
            if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                if (preg_match('/^\d{4}$/', $periodo)) {
                    $periodo = $periodo . '-' . (new DateTime())->format('m');
                } else {
                    $periodo = (new DateTime())->format('Y-m');
                }
            }
        }

        $empleadoId = $request->query->get('empleado');
        $modalidadId = $request->query->get('modalidad');

        $empleadosActivos = $entityManager->getRepository(Empleado::class)->findBy(
            ['activo' => true],
            ['apellido' => 'ASC', 'nombre' => 'ASC']
        );

        $empleados = $empleadosActivos;
        if ($empleadoId) {
            $empleadoFiltrado = $entityManager->getRepository(Empleado::class)->find($empleadoId);
            if ($empleadoFiltrado && $empleadoFiltrado->isActivo()) {
                $empleados = [$empleadoFiltrado];
            }
        }

        if ($modalidadId) {
            $empleados = array_filter($empleados, function (Empleado $empleado) use ($modalidadId) {
                $modalidad = $empleado->getModalidadPago();
                return $modalidad && $modalidad->getId() == $modalidadId;
            });
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Liquidaciones ' . $periodo);

        if ($vista === 'anual') {
            $datosAnuales = $this->buildGridAnual($empleados, $periodo, $entityManager);
            $this->buildExcelAnual($sheet, $datosAnuales['grid'], $periodo, $datosAnuales['meses_nombres'], $datosAnuales['totales_meses'], $datosAnuales['total_general_anual']);
        } else {
            $semanas = $this->buildSemanasDelMes($periodo);
            $grid = [];
            foreach ($empleados as $empleado) {
                $grid[] = $this->buildFila($empleado, $periodo, $semanas, $entityManager);
            }

            if ($vista === 'mensual') {
                $this->buildExcelMensual($sheet, $grid, $periodo);
            } else {
                $this->buildExcelSemanal($sheet, $grid, $semanas, $periodo);
            }
        }

        $filename = 'liquidaciones-' . $periodo . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildExcelSemanal(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $grid, array $semanas, string $periodo): void
    {
        $totalesPorSemana = array_fill(0, count($semanas), '0');
        $totalConceptosMensual = '0';
        $totalGeneral = '0';

        foreach ($grid as $fila) {
            foreach ($fila['semanas'] as $index => $semana) {
                if ($semana['liquidacion']) {
                    $totalesPorSemana[$index] = Decimal::add(
                        $totalesPorSemana[$index],
                        (string) $semana['liquidacion']->getTotalAPagar(),
                        2
                    );
                }
            }

            if ($fila['resumen']) {
                $totalConceptosMensual = Decimal::add(
                    $totalConceptosMensual,
                    (string) $fila['resumen']->getTotalConceptos(),
                    2
                );
                $totalGeneral = Decimal::add($totalGeneral, (string) $fila['resumen']->getTotalAPagar(), 2);
            }
        }

        $sheet->setCellValue('A1', 'Resumen de liquidaciones — ' . $periodo);
        $sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(2 + count($semanas) + 2) . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Empleado');
        $sheet->setCellValue('B2', 'Modalidad');

        $col = 3;
        foreach ($semanas as $semana) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', "Sem " . $semana['numero'] . "\n" . $semana['label']);
            $col++;
        }
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Conceptos');
        $col++;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2', 'Total');

        $headerRange = 'A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(2)->setRowHeight(35);

        $fila = 3;
        foreach ($grid as $row) {
            $sheet->setCellValue('A' . $fila, $row['empleado']->getNombreCompleto());
            $sheet->setCellValue('B' . $fila, $row['modalidad']);

            $col = 3;
            $esSemanal = $row['empleado']->getModalidadPago() && $row['empleado']->getModalidadPago()->getCodigoInterno() == ConstanteTipoModalidadPago::SEMANAL;
            foreach ($row['semanas'] as $semana) {
                if ($semana['liquidacion']) {
                    if ($esSemanal) {
                        $neto = $this->formatMoney($semana['liquidacion']->getSueldoNeto());
                        $totalConceptos = (float) $semana['liquidacion']->getTotalConceptos();
                        if (abs($totalConceptos) > 0.001) {
                            $signo = $totalConceptos >= 0 ? '+' : '-';
                            $conceptos = $this->formatMoney(abs($totalConceptos));
                            $valor = $neto . ' (' . $signo . $conceptos . ')';
                        } else {
                            $valor = $neto;
                        }
                        $sheet->setCellValueExplicit(
                            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila,
                            $valor,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    } else {
                        $valor = (float) $semana['liquidacion']->getTotalAPagar();
                        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $valor);
                    }
                } else {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, '');
                }
                $col++;
            }

            $conceptosMensual = $row['resumen'] ? (float) $row['resumen']->getTotalConceptos() : 0;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $conceptosMensual);
            $col++;

            $total = $row['resumen'] ? (float) $row['resumen']->getTotalAPagar() : 0;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $total);

            $fila++;
        }

        $filaTotal = $fila;
        $sheet->setCellValue('A' . $filaTotal, 'TOTAL');

        $col = 3;
        foreach ($totalesPorSemana as $total) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, (float) $total);
            $col++;
        }
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, (float) $totalConceptosMensual);
        $col++;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal, (float) $totalGeneral);

        $totalRange = 'A' . $filaTotal . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal;
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E2E2');
        $sheet->getStyle($totalRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('A2:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . ($filaTotal - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('C3:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $filaTotal)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range(1, $col) as $columnIndex) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function buildExcelMensual(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $grid, string $periodo): void
    {
        $totalesMensuales = [
            'bruto' => '0',
            'deducciones' => '0',
            'contribuciones' => '0',
            'neto' => '0',
            'conceptos' => '0',
            'total' => '0',
        ];

        foreach ($grid as $fila) {
            if ($fila['resumen']) {
                $totalesMensuales['bruto'] = Decimal::add($totalesMensuales['bruto'], (string) $fila['resumen']->getSueldoBruto(), 2);
                $totalesMensuales['deducciones'] = Decimal::add($totalesMensuales['deducciones'], (string) $fila['resumen']->getDeducciones(), 2);
                $totalesMensuales['contribuciones'] = Decimal::add($totalesMensuales['contribuciones'], (string) $fila['resumen']->getContribuciones(), 2);
                $totalesMensuales['neto'] = Decimal::add($totalesMensuales['neto'], (string) $fila['resumen']->getSueldoNeto(), 2);
                $conceptosFila = Decimal::add((string) $fila['resumen']->getTotalConceptosSemanas(), (string) $fila['resumen']->getTotalConceptos(), 2);
                $totalesMensuales['conceptos'] = Decimal::add($totalesMensuales['conceptos'], $conceptosFila, 2);
                $totalesMensuales['total'] = Decimal::add($totalesMensuales['total'], (string) $fila['resumen']->getTotalAPagar(), 2);
            }
        }

        $sheet->setCellValue('A1', 'Resumen de liquidaciones — ' . $periodo);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Empleado', 'Modalidad', 'Bruto', 'Deducciones', 'Contribuciones', 'Neto', 'Conceptos', 'Total'];
        foreach ($headers as $index => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($colLetter . '2', $header);
        }

        $headerRange = 'A2:H2';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(35);

        $fila = 3;
        foreach ($grid as $row) {
            $sheet->setCellValue('A' . $fila, $row['empleado']->getNombreCompleto());
            $sheet->setCellValue('B' . $fila, $row['modalidad']);

            $bruto = $row['resumen'] ? (float) $row['resumen']->getSueldoBruto() : 0;
            $deducciones = $row['resumen'] ? (float) $row['resumen']->getDeducciones() : 0;
            $contribuciones = $row['resumen'] ? (float) $row['resumen']->getContribuciones() : 0;
            $neto = $row['resumen'] ? (float) $row['resumen']->getSueldoNeto() : 0;
            $conceptos = $row['resumen'] ? (float) Decimal::add((string) $row['resumen']->getTotalConceptosSemanas(), (string) $row['resumen']->getTotalConceptos(), 2) : 0;
            $total = $row['resumen'] ? (float) $row['resumen']->getTotalAPagar() : 0;

            $sheet->setCellValue('C' . $fila, $bruto);
            $sheet->setCellValue('D' . $fila, $deducciones);
            $sheet->setCellValue('E' . $fila, $contribuciones);
            $sheet->setCellValue('F' . $fila, $neto);
            $sheet->setCellValue('G' . $fila, $conceptos);
            $sheet->setCellValue('H' . $fila, $total);

            $fila++;
        }

        $filaTotal = $fila;
        $sheet->setCellValue('A' . $filaTotal, 'TOTAL');
        $sheet->setCellValue('B' . $filaTotal, '');
        $sheet->setCellValue('C' . $filaTotal, (float) $totalesMensuales['bruto']);
        $sheet->setCellValue('D' . $filaTotal, (float) $totalesMensuales['deducciones']);
        $sheet->setCellValue('E' . $filaTotal, (float) $totalesMensuales['contribuciones']);
        $sheet->setCellValue('F' . $filaTotal, (float) $totalesMensuales['neto']);
        $sheet->setCellValue('G' . $filaTotal, (float) $totalesMensuales['conceptos']);
        $sheet->setCellValue('H' . $filaTotal, (float) $totalesMensuales['total']);

        $totalRange = 'A' . $filaTotal . ':H' . $filaTotal;
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E2E2');
        $sheet->getStyle($totalRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('A2:H' . ($filaTotal - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('C3:H' . $filaTotal)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range(1, 8) as $columnIndex) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function buildGridAnual(array $empleados, string $anio, EntityManagerInterface $entityManager): array
    {
        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $liquidaciones = $entityManager->getRepository(Liquidacion::class)->createQueryBuilder('l')
            ->where('l.padre IS NULL')
            ->andWhere('l.periodo LIKE :anio')
            ->setParameter('anio', $anio . '-%')
            ->getQuery()
            ->getResult();

        $resumenesPorEmpleado = [];
        foreach ($liquidaciones as $liq) {
            $empId = $liq->getEmpleado()->getId();
            $mesNum = (int) substr($liq->getPeriodo(), 5, 2);
            $resumenesPorEmpleado[$empId][$mesNum] = $liq;
        }

        $grid = [];
        $totalesMeses = array_fill(1, 12, '0');
        $totalGeneralAnual = '0';

        foreach ($empleados as $empleado) {
            $empId = $empleado->getId();
            $filaMeses = [];
            $totalEmpleado = '0';

            for ($m = 1; $m <= 12; $m++) {
                $resumen = $resumenesPorEmpleado[$empId][$m] ?? null;
                $monto = '0';
                if ($resumen !== null) {
                    $monto = Decimal::add((string) $resumen->getSueldoNeto(), (string) $resumen->getDeducciones(), 2);
                    $monto = Decimal::add($monto, (string) $resumen->getContribuciones(), 2);
                }

                $totalEmpleado = Decimal::add($totalEmpleado, $monto, 2);
                $totalesMeses[$m] = Decimal::add($totalesMeses[$m], $monto, 2);

                $filaMeses[] = [
                    'numero' => $m,
                    'nombre' => $mesesNombres[$m],
                    'resumen' => $resumen,
                    'monto' => $this->formatMoney($monto),
                    'monto_raw' => $monto,
                ];
            }

            $totalGeneralAnual = Decimal::add($totalGeneralAnual, $totalEmpleado, 2);

            $grid[] = [
                'empleado' => $empleado,
                'modalidad' => $empleado->getModalidadPago() ? $empleado->getModalidadPago()->getNombre() : '-',
                'meses' => $filaMeses,
                'total' => $this->formatMoney($totalEmpleado),
                'total_raw' => $totalEmpleado,
            ];
        }

        return [
            'grid' => $grid,
            'meses_nombres' => array_values($mesesNombres),
            'totales_meses' => array_values($totalesMeses),
            'total_general_anual' => $totalGeneralAnual,
        ];
    }

    private function buildExcelAnual(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $gridAnual, string $periodo, array $mesesNombres, array $totalesMeses, string $totalGeneralAnual): void
    {
        $sheet->setCellValue('A1', 'Resumen de liquidaciones — ' . $periodo);
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = array_merge(['Empleado'], $mesesNombres, ['Total']);
        foreach ($headers as $index => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($colLetter . '2', $header);
        }

        $headerRange = 'A2:N2';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFC000');
        $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle($headerRange)->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(35);

        $fila = 3;
        foreach ($gridAnual as $row) {
            $sheet->setCellValue('A' . $fila, $row['empleado']->getNombreCompleto());

            $col = 2;
            foreach ($row['meses'] as $mes) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($colLetter . $fila, (float) $mes['monto_raw']);
                $col++;
            }

            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . $fila, (float) $row['total_raw']);

            $fila++;
        }

        $filaTotal = $fila;
        $sheet->setCellValue('A' . $filaTotal, 'TOTAL');

        $col = 2;
        foreach ($totalesMeses as $totalMes) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($colLetter . $filaTotal, (float) $totalMes);
            $col++;
        }

        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($colLetter . $filaTotal, (float) $totalGeneralAnual);

        $totalRange = 'A' . $filaTotal . ':N' . $filaTotal;
        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E2E2E2');
        $sheet->getStyle($totalRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('A2:N' . ($filaTotal - 1))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $sheet->getStyle('B3:N' . $filaTotal)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range(1, 14) as $columnIndex) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @Route("/generar", name="liquidacion_generar", methods={"POST"})
     */
    public function generar(Request $request, EntityManagerInterface $entityManager): Response
    {
        $periodo = $request->request->get('periodo', (new DateTime())->format('Y-m'));
        $empleadoId = $request->request->get('empleado');
        $modalidadId = $request->request->get('modalidad');

        if (!$this->isCsrfTokenValid('generar_' . $periodo, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $periodo, 'empleado' => $empleadoId, 'modalidad' => $modalidadId]);
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            $periodo = (new DateTime())->format('Y-m');
        }

        $semanas = $this->buildSemanasDelMes($periodo);

        $empleadosActivos = $entityManager->getRepository(Empleado::class)->findBy(
            ['activo' => true],
            ['apellido' => 'ASC', 'nombre' => 'ASC']
        );

        $empleados = $empleadosActivos;

        if ($empleadoId) {
            $empleado = $entityManager->getRepository(Empleado::class)->find($empleadoId);
            if (!$empleado || !$empleado->isActivo()) {
                $this->addFlash('error', 'Empleado no encontrado.');
                return $this->redirectToRoute('liquidacion_index', ['periodo' => $periodo, 'modalidad' => $modalidadId]);
            }
            $empleados = [$empleado];
        }

        if ($modalidadId) {
            $empleados = array_filter($empleados, function (Empleado $empleado) use ($modalidadId) {
                $modalidad = $empleado->getModalidadPago();
                return $modalidad && $modalidad->getId() == $modalidadId;
            });
        }

        $estadoBorrador = $entityManager->getRepository(EstadoLiquidacion::class)
            ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::BORRADOR);

        if (!$estadoBorrador) {
            $this->addFlash('error', 'No existe el estado BORRADOR.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $periodo]);
        }

        $creadas = 0;
        foreach ($empleados as $empleado) {
            $modalidad = $empleado->getModalidadPago();
            if (!$modalidad) {
                continue;
            }

            $esSemanal = $modalidad->getCodigoInterno() === ConstanteTipoModalidadPago::SEMANAL;
            $esMensual = $modalidad->getCodigoInterno() === ConstanteTipoModalidadPago::MENSUAL;

            if ($esSemanal) {
                $rangoMes = [
                    'inicio' => new DateTime($periodo . '-01'),
                    'fin' => (new DateTime($periodo . '-01'))->modify('last day of this month'),
                ];

                $this->crearLiquidacionSiNoExiste($empleado, $periodo, $rangoMes, $estadoBorrador, $modalidad, $entityManager);

                // Necesario para que el resumen tenga id antes de buscarlo y vincular semanas hijas
                $entityManager->flush();

                $padre = $entityManager->getRepository(Liquidacion::class)->findOneBy([
                    'empleado' => $empleado,
                    'fechaDesde' => $rangoMes['inicio'],
                    'fechaHasta' => $rangoMes['fin'],
                    'padre' => null,
                ]);

                if ($padre) {
                    foreach ($semanas as $semana) {
                        if ($this->crearLiquidacionSiNoExiste($empleado, $periodo, $semana, $estadoBorrador, $modalidad, $entityManager, $padre)) {
                            $creadas++;
                        }
                    }
                }
            } elseif ($esMensual) {
                $semanaMes = [
                    'inicio' => new DateTime($periodo . '-01'),
                    'fin' => (new DateTime($periodo . '-01'))->modify('last day of this month'),
                ];
                if ($this->crearLiquidacionSiNoExiste($empleado, $periodo, $semanaMes, $estadoBorrador, $modalidad, $entityManager)) {
                    $creadas++;
                }
            }
        }

        $entityManager->flush();

        $this->addFlash('success', 'Se generaron ' . $creadas . ' liquidaciones en borrador.');

        return $this->redirectToRoute('liquidacion_index', ['periodo' => $periodo, 'empleado' => $empleadoId, 'modalidad' => $modalidadId]);
    }

    /**
     * @Route("/{id}", name="liquidacion_show", methods={"GET"})
     */
    public function show(Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        $totalPagado = $this->getTotalPagado($liquidacion);
        $restante = Decimal::sub((string) $liquidacion->getTotalAPagar(), $totalPagado, 2);

        $pago = new PagoEmpleado();
        $pago->setFecha(new DateTime());
        $pago->setImporte($restante);

        $pagoForm = $this->createForm(PagoEmpleadoType::class, $pago, [
            'action' => $this->generateUrl('liquidacion_pagar', ['id' => $liquidacion->getId()]),
            'method' => 'POST',
        ]);

        $incluirSueldo = !($liquidacion->getDetallesSemanales()->count() > 0);
        $incluirContribuciones = $liquidacion->getTipoModalidadPago()
            && $liquidacion->getTipoModalidadPago()->getCodigoInterno() === ConstanteTipoModalidadPago::MENSUAL;
        $incluirConceptos = $liquidacion->getPadre() === null;

        $editable = $this->puedeEditarContenido($liquidacion);
        $form = $this->createForm(LiquidacionType::class, $liquidacion, [
            'action' => $this->generateUrl('liquidacion_guardar', ['id' => $liquidacion->getId()]),
            'method' => 'POST',
            'incluir_sueldo' => $incluirSueldo,
            'incluir_contribuciones' => $incluirContribuciones,
            'incluir_conceptos' => $incluirConceptos,
            'editable' => $editable,
        ]);

        $tiposConcepto = $entityManager->getRepository(TipoConceptoLiquidacion::class)
            ->createQueryBuilder('t')
            ->where('t.habilitado = 1')
            ->andWhere('t.fechaBaja IS NULL')
            ->orderBy('t.nombre', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('liquidacion/show.html.twig', [
            'liquidacion' => $liquidacion,
            'form' => $form->createView(),
            'pagoForm' => $pagoForm->createView(),
            'editable' => $editable,
            'totalPagado' => $totalPagado,
            'restante' => $restante,
            'tiposConcepto' => $tiposConcepto,
        ]);
    }


    /**
     * @Route("/{id}/semana", name="liquidacion_show_semana", methods={"GET"})
     */
    public function showSemana(Liquidacion $liquidacion, Request $request): Response
    {
        if ($liquidacion->getPadre() === null) {
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $returnTo = $request->query->get('returnTo');
        if ($returnTo && !str_starts_with($returnTo, '/')) {
            $returnTo = null;
        }

        $totalPagado = $this->getTotalPagado($liquidacion);
        $restante = Decimal::sub((string) $liquidacion->getTotalAPagar(), $totalPagado, 2);

        $editable = $this->puedeEditarContenido($liquidacion);

        return $this->render('liquidacion/show_semana.html.twig', [
            'liquidacion' => $liquidacion,
            'returnTo' => $returnTo,
            'totalPagado' => $totalPagado,
            'restante' => $restante,
            'editable' => $editable,
        ]);
    }

    /**
     * @Route("/concepto/{id}/eliminar", name="liquidacion_concepto_eliminar", methods={"POST"})
     */
    public function eliminarConcepto(Request $request, ConceptoLiquidacion $concepto, EntityManagerInterface $entityManager): Response
    {
        $liquidacion = $concepto->getLiquidacion();
        $returnTo = $request->request->get('returnTo');

        if (!$liquidacion) {
            $this->addFlash('error', 'Concepto no asociado a una liquidación.');
            return $this->redirectToRoute('liquidacion_index');
        }

        if (!$this->isCsrfTokenValid('eliminar_concepto_' . $concepto->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show_semana', ['id' => $liquidacion->getId(), 'returnTo' => $returnTo]);
        }

        if (!$this->puedeEditarContenido($liquidacion)) {
            $this->addFlash('error', 'La liquidación no puede editarse en este estado.');
            return $this->redirectToRoute('liquidacion_show_semana', ['id' => $liquidacion->getId(), 'returnTo' => $returnTo]);
        }

        $padre = $liquidacion->getPadre();

        $liquidacion->removeConcepto($concepto);
        $entityManager->remove($concepto);
        $liquidacion->recalcularTotal();

        if ($padre !== null) {
            $padre->recalcularTotal();
        }

        $entityManager->flush();

        $this->addFlash('success', 'Concepto eliminado correctamente.');

        return $this->redirectToRoute('liquidacion_show_semana', ['id' => $liquidacion->getId(), 'returnTo' => $returnTo]);
    }

    /**
     * @Route("/{id}/guardar", name="liquidacion_guardar", methods={"POST"})
     */
    public function guardar(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->puedeEditarContenido($liquidacion)) {
            $this->addFlash('error', 'La liquidación no puede editarse en este estado.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $incluirSueldo = !($liquidacion->getDetallesSemanales()->count() > 0);
        $incluirContribuciones = $liquidacion->getTipoModalidadPago()
            && $liquidacion->getTipoModalidadPago()->getCodigoInterno() === ConstanteTipoModalidadPago::MENSUAL;
        $incluirConceptos = $liquidacion->getPadre() === null;

        $form = $this->createForm(LiquidacionType::class, $liquidacion, [
            'incluir_sueldo' => $incluirSueldo,
            'incluir_contribuciones' => $incluirContribuciones,
            'incluir_conceptos' => $incluirConceptos,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($incluirConceptos) {
                $submittedData = $request->request->get('liquidacion', []);
                $submittedConceptos = $submittedData['conceptos'] ?? [];

                $conceptoRepo = $entityManager->getRepository(ConceptoLiquidacion::class);
                $tipoConceptoRepo = $entityManager->getRepository(TipoConceptoLiquidacion::class);

                $existingById = [];
                foreach ($liquidacion->getConceptos() as $concepto) {
                    $existingById[$concepto->getId()] = $concepto;
                }

                $submittedIds = [];
                $newCollection = new \Doctrine\Common\Collections\ArrayCollection();

                foreach ($submittedConceptos as $data) {
                    $id = !empty($data['id']) ? (int) $data['id'] : null;

                    if ($id && isset($existingById[$id])) {
                        $concepto = $existingById[$id];
                        $submittedIds[] = $id;
                    } else {
                        $concepto = new ConceptoLiquidacion();
                    }

                    $tipoConcepto = !empty($data['tipoConceptoLiquidacion']) ? $tipoConceptoRepo->find($data['tipoConceptoLiquidacion']) : null;
                    $cantidad = str_replace(['.', ','], ['', '.'], $data['cantidad'] ?? '0');
                    $valorUnitario = str_replace(['.', ','], ['', '.'], $data['valorUnitario'] ?? '0');

                    $concepto->setTipoConceptoLiquidacion($tipoConcepto);
                    $concepto->setDescripcion($data['descripcion'] ?? null);
                    $concepto->setCantidad($cantidad);
                    $concepto->setValorUnitario($valorUnitario);
                    $concepto->setImporte(Decimal::mul($cantidad, $valorUnitario, 2));
                    $concepto->setLiquidacion($liquidacion);

                    $newCollection->add($concepto);
                }

                foreach ($existingById as $id => $concepto) {
                    if (!in_array($id, $submittedIds, true)) {
                        $entityManager->remove($concepto);
                    }
                }

                $liquidacion->getConceptos()->clear();
                foreach ($newCollection as $concepto) {
                    $liquidacion->addConcepto($concepto);
                }
            }

            $estadoBorrador = $entityManager->getRepository(EstadoLiquidacion::class)
                ->findOneByCodigoInterno(ConstanteEstadoLiquidacion::BORRADOR);

            if ($estadoBorrador) {
                $liquidacion->setEstado($estadoBorrador);
            }

            $liquidacion->recalcularTotal();

            $padre = $liquidacion->getPadre();
            if ($padre !== null) {
                $padre->recalcularTotal();
            }

            $entityManager->flush();

            if ($request->request->get('accion') === 'aprobar') {
                foreach ($liquidacion->getDetallesSemanales() as $detalle) {
                    $codigoInterno = $detalle->getEstado() ? $detalle->getEstado()->getCodigoInterno() : null;
                    if ($codigoInterno !== ConstanteEstadoLiquidacion::APROBADA && $codigoInterno !== ConstanteEstadoLiquidacion::PAGADA) {
                        $this->addFlash('error', 'Debe aprobarse (o pagarse) todas las liquidaciones semanales antes de aprobar la liquidación mensual.');
                        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
                    }
                }

                if ($liquidacion->getPadre() !== null) {
                    if (Decimal::comp((string) $liquidacion->getSueldoBruto(), '0', 2) === 0) {
                        $this->addFlash('warning', 'Se aprobó una liquidación con sueldo bruto igual a 0.');
                    }
                } elseif ($liquidacion->getDetallesSemanales()->count() === 0) {
                    if (Decimal::comp((string) $liquidacion->getSueldoBruto(), '0', 2) === 0) {
                        $this->addFlash('error', 'No se puede aprobar una liquidación con sueldo bruto igual a 0.');
                        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
                    }
                }

                $liquidacionService->aprobar($liquidacion);
                $entityManager->flush();

                $this->addFlash('success', 'Liquidación guardada y aprobada correctamente.');
            } else {
                $this->addFlash('success', 'Liquidación guardada correctamente.');
            }

            $redirectId = $padre !== null ? $padre->getId() : $liquidacion->getId();
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        $this->addFlash('error', 'Verifique los datos ingresados.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/aprobar", name="liquidacion_aprobar", methods={"POST"})
     */
    public function aprobar(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('aprobar_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        if (!$liquidacion->getEstado() || $liquidacion->getEstado()->getCodigoInterno() !== ConstanteEstadoLiquidacion::BORRADOR) {
            $this->addFlash('error', 'La liquidación debe estar en borrador para poder aprobarse.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        foreach ($liquidacion->getDetallesSemanales() as $detalle) {
            $codigoInterno = $detalle->getEstado() ? $detalle->getEstado()->getCodigoInterno() : null;
            if ($codigoInterno !== ConstanteEstadoLiquidacion::APROBADA && $codigoInterno !== ConstanteEstadoLiquidacion::PAGADA) {
                $this->addFlash('error', 'Debe aprobarse (o pagarse) todas las liquidaciones semanales antes de aprobar la liquidación mensual.');
                return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
            }
        }

        if ($liquidacion->getPadre() !== null) {
            if (Decimal::comp((string) $liquidacion->getSueldoBruto(), '0', 2) === 0) {
                $this->addFlash('warning', 'Se aprobó una liquidación con sueldo bruto igual a 0.');
            }
        } elseif ($liquidacion->getDetallesSemanales()->count() === 0) {
            if (Decimal::comp((string) $liquidacion->getSueldoBruto(), '0', 2) === 0) {
                $this->addFlash('error', 'No se puede aprobar una liquidación con sueldo bruto igual a 0.');
                return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
            }
        }

        $liquidacionService->aprobar($liquidacion);
        $entityManager->flush();

        $this->addFlash('success', 'Liquidación aprobada correctamente.');

        $padre = $liquidacion->getPadre();
        $redirectId = $padre !== null ? $padre->getId() : $liquidacion->getId();

        return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
    }

    /**
     * @Route("/{id}/pagar", name="liquidacion_pagar", methods={"POST"})
     */
    public function pagar(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        $padre = $liquidacion->getPadre();
        $redirectId = $padre !== null ? $padre->getId() : $liquidacion->getId();

        $estadoCodigo = $liquidacion->getEstado() ? $liquidacion->getEstado()->getCodigoInterno() : null;

        if (!$estadoCodigo || ($estadoCodigo !== ConstanteEstadoLiquidacion::APROBADA && $estadoCodigo !== ConstanteEstadoLiquidacion::PAGADA)) {
            $this->addFlash('error', 'La liquidación debe estar aprobada para registrar pagos.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        if ($liquidacion->getPadre() === null) {
            foreach ($liquidacion->getDetallesSemanales() as $detalle) {
                if (!$detalle->getEstado() || $detalle->getEstado()->getCodigoInterno() !== ConstanteEstadoLiquidacion::PAGADA) {
                    $this->addFlash('error', 'Debe pagarse todas las liquidaciones semanales antes de pagar la liquidación mensual.');
                    return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
                }
            }
        }

        $pago = new PagoEmpleado();
        $pago->setLiquidacion($liquidacion);

        $form = $this->createForm(PagoEmpleadoType::class, $pago);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $totalPagado = $this->getTotalPagado($liquidacion);
            $restante = Decimal::sub((string) $liquidacion->getTotalAPagar(), (string) $totalPagado, 2);

            if (Decimal::comp((string) $pago->getImporte(), $restante, 2) > 0) {
                $this->addFlash('error', 'El pago no puede superar el restante a abonar ($' . $this->formatMoney($restante) . ').');
                return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
            }

            $liquidacion->addPago($pago);
            $entityManager->persist($pago);

            $totalPagado = Decimal::add((string) $totalPagado, (string) $pago->getImporte(), 2);

            if (Decimal::comp($totalPagado, (string) $liquidacion->getTotalAPagar(), 2) >= 0) {
                $liquidacionService->marcarComoPagada($liquidacion);
                $this->addFlash('success', 'Pago registrado. La liquidación quedó pagada.');
            } else {
                $restante = Decimal::sub((string) $liquidacion->getTotalAPagar(), $totalPagado, 2);
                $this->addFlash('success', 'Pago registrado. Resta abonar $' . $this->formatMoney($restante) . '.');
            }

            $entityManager->flush();

            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        $this->addFlash('error', 'Verifique los datos del pago.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
    }

    /**
     * @Route("/{id}/revertir", name="liquidacion_revertir", methods={"POST"})
     */
    public function revertir(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('revertir_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        if ($liquidacion->getPadre() !== null) {
            $this->addFlash('error', 'Debe revertirse el pago desde la liquidación mensual.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getPadre()->getId()]);
        }

        if (!$liquidacion->getEstado() || $liquidacion->getEstado()->getCodigoInterno() !== ConstanteEstadoLiquidacion::PAGADA) {
            $this->addFlash('error', 'La liquidación debe estar pagada para poder revertirse.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $liquidacionService->revertirPago($liquidacion);
        $entityManager->flush();

        $this->addFlash('success', 'Se revirtió el pago de la liquidación correctamente.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/revertir-aprobacion", name="liquidacion_revertir_aprobacion", methods={"POST"})
     */
    public function revertirAprobacion(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        $padre = $liquidacion->getPadre();
        $redirectId = $padre !== null ? $padre->getId() : $liquidacion->getId();

        if (!$this->isCsrfTokenValid('revertir_aprobacion_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        if (!$liquidacion->getEstado() || $liquidacion->getEstado()->getCodigoInterno() !== ConstanteEstadoLiquidacion::APROBADA) {
            $this->addFlash('error', 'La liquidación debe estar aprobada para poder revertirse a borrador.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        if ($padre !== null) {
            $estadoPadre = $padre->getEstado() ? $padre->getEstado()->getCodigoInterno() : null;
            if ($estadoPadre !== ConstanteEstadoLiquidacion::BORRADOR) {
                $this->addFlash('error', 'Debe revertirse primero la liquidación mensual.');
                return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
            }
        }

        $liquidacionService->revertirAprobacion($liquidacion);
        $entityManager->flush();

        $this->addFlash('success', 'Se revirtió la aprobación de la liquidación correctamente.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
    }

    /**
     * @Route("/{id}/anular", name="liquidacion_anular", methods={"POST"})
     */
    public function anular(Request $request, Liquidacion $liquidacion, LiquidacionService $liquidacionService, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('anular_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        if ($liquidacion->getEstado() && $liquidacion->getEstado()->getCodigoInterno() === ConstanteEstadoLiquidacion::ANULADA) {
            $this->addFlash('error', 'La liquidación ya se encuentra anulada.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
        }

        $motivo = $request->request->get('motivo', 'Liquidación anulada.');

        $liquidacionService->anular($liquidacion, $motivo);
        $entityManager->flush();

        $this->addFlash('success', 'Liquidación anulada correctamente.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $liquidacion->getId()]);
    }

    /**
     * @Route("/{id}/guardar-semana", name="liquidacion_guardar_semana", methods={"POST"})
     */
    public function guardarSemana(Request $request, Liquidacion $liquidacion, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('guardar_semana_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
        }

        if (!$this->puedeEditarContenido($liquidacion)) {
            $this->addFlash('error', 'La liquidación no puede editarse en este estado.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
        }

        if ($liquidacion->getPadre() === null) {
            $this->addFlash('error', 'Solo se pueden editar liquidaciones semanales desde esta acción.');
            return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
        }

        $sueldoBruto = str_replace(',', '.', $request->request->get('sueldoBruto', '0'));
        $deducciones = str_replace(',', '.', $request->request->get('deducciones', '0'));
        $contribuciones = str_replace(',', '.', $request->request->get('contribuciones', '0'));

        $liquidacion->setSueldoBruto($sueldoBruto);
        $liquidacion->setDeducciones($deducciones);
        $liquidacion->setContribuciones($contribuciones);
        $liquidacion->recalcularTotal();

        $padre = $liquidacion->getPadre();
        $padre->recalcularTotal();

        $entityManager->flush();

        $this->addFlash('success', 'Liquidación semanal guardada correctamente.');

        $return = $request->request->get('return');
        if ($return === 'show' && $padre !== null) {
            return $this->redirectToRoute('liquidacion_show', ['id' => $padre->getId()]);
        }

        return $this->redirectToRoute('liquidacion_index', ['periodo' => $liquidacion->getPeriodo()]);
    }

    /**
     * @Route("/{id}/agregar-concepto", name="liquidacion_agregar_concepto_semana", methods={"POST"})
     */
    public function agregarConceptoSemana(Request $request, Liquidacion $liquidacion, EntityManagerInterface $entityManager): Response
    {
        $padre = $liquidacion->getPadre();
        $redirectId = $padre !== null ? $padre->getId() : $liquidacion->getId();

        if (!$this->isCsrfTokenValid('agregar_concepto_semana_' . $liquidacion->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        if ($liquidacion->getPadre() === null) {
            $this->addFlash('error', 'Solo se pueden agregar conceptos a liquidaciones semanales desde esta acción.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        if (!$this->puedeEditarContenido($liquidacion)) {
            $this->addFlash('error', 'La liquidación no puede editarse en este estado.');
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        $tipoConceptoId = $request->request->get('tipoConceptoLiquidacion');
        $tipoConcepto = $tipoConceptoId ? $entityManager->getRepository(TipoConceptoLiquidacion::class)->find($tipoConceptoId) : null;
        $cantidad = str_replace(['.', ','], ['', '.'], $request->request->get('cantidad', '0'));
        $valorUnitario = str_replace(['.', ','], ['', '.'], $request->request->get('valorUnitario', '0'));
        $descripcion = $request->request->get('descripcion');

        if (!$tipoConcepto || Decimal::comp($cantidad, '0', 2) <= 0 || Decimal::comp($valorUnitario, '0', 2) <= 0) {
            $this->addFlash('error', 'Debe completar el tipo de concepto, cantidad y valor unitario (mayores a 0).');
            return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
        }

        $concepto = new ConceptoLiquidacion();
        $concepto->setTipoConceptoLiquidacion($tipoConcepto);
        $concepto->setDescripcion($descripcion);
        $concepto->setCantidad($cantidad);
        $concepto->setValorUnitario($valorUnitario);
        $concepto->setImporte(Decimal::mul($cantidad, $valorUnitario, 2));
        $liquidacion->addConcepto($concepto);

        $entityManager->persist($concepto);

        $liquidacion->recalcularTotal();
        $padre->recalcularTotal();

        $entityManager->flush();

        $this->addFlash('success', 'Concepto agregado correctamente a la liquidación semanal.');

        return $this->redirectToRoute('liquidacion_show', ['id' => $redirectId]);
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

    private function buildFila(Empleado $empleado, string $periodo, array $semanas, EntityManagerInterface $entityManager): array
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

        $conceptosMensuales = '0';
        if ($resumen !== null) {
            $conceptosMensuales = Decimal::add((string) $resumen->getTotalConceptosSemanas(), (string) $resumen->getTotalConceptos(), 2);
        }

        $fila = [
            'empleado' => $empleado,
            'resumen' => $resumen,
            'modalidad' => $empleado->getModalidadPago() ? $empleado->getModalidadPago()->getNombre() : '-',
            'semanas' => [],
            'total' => $resumen !== null ? $this->formatMoney($resumen->getTotalAPagar()) : $this->formatMoney('0'),
            'bruto' => $resumen !== null ? $this->formatMoney($resumen->getSueldoBruto()) : $this->formatMoney('0'),
            'deducciones' => $resumen !== null ? $this->formatMoney($resumen->getDeducciones()) : $this->formatMoney('0'),
            'contribuciones' => $resumen !== null ? $this->formatMoney($resumen->getContribuciones()) : $this->formatMoney('0'),
            'neto' => $resumen !== null ? $this->formatMoney($resumen->getSueldoNeto()) : $this->formatMoney('0'),
            'conceptos' => $this->formatMoney($conceptosMensuales),
        ];

        foreach ($semanas as $semana) {
            $fila['semanas'][] = [
                'liquidacion' => null,
                'valor' => null,
            ];
        }

        foreach ($detalles as $key => $liquidacion) {
            foreach ($semanas as $index => $semana) {
                if ($semana['fin']->format('Y-m-d') === $key) {
                    $fila['semanas'][$index]['liquidacion'] = $liquidacion;
                    $fila['semanas'][$index]['valor'] = $this->formatMoney($liquidacion->getTotalAPagar());
                    break;
                }
            }
        }

        return $fila;
    }

    private function crearLiquidacionSiNoExiste(
        Empleado $empleado,
        string $periodo,
        array $rango,
        EstadoLiquidacion $estadoBorrador,
        \App\Entity\TipoModalidadPago $modalidad,
        EntityManagerInterface $entityManager,
        ?Liquidacion $padre = null
    ): bool {
        $criteria = [
            'empleado' => $empleado,
            'fechaDesde' => $rango['inicio'],
            'fechaHasta' => $rango['fin'],
        ];

        if ($padre === null) {
            $criteria['padre'] = null;
        } else {
            $criteria['padre'] = $padre;
        }

        $existente = $entityManager->getRepository(Liquidacion::class)->findOneBy($criteria);

        if ($existente) {
            return false;
        }

        $liquidacion = new Liquidacion();
        $liquidacion->setEmpleado($empleado);
        $liquidacion->setPeriodo($periodo);
        $liquidacion->setFechaDesde($rango['inicio']);
        $liquidacion->setFechaHasta($rango['fin']);
        $liquidacion->setTipoModalidadPago($modalidad);
        $liquidacion->setEstado($estadoBorrador);
        $liquidacion->setSueldoBruto(0);
        $liquidacion->setDeducciones(0);
        $liquidacion->setContribuciones(0);
        $liquidacion->setTotalAPagar(0);

        if ($padre !== null) {
            $padre->addDetalleSemanal($liquidacion);
        }

        $entityManager->persist($liquidacion);

        return true;
    }

    private function getTotalPagado(Liquidacion $liquidacion): string
    {
        $total = '0';
        foreach ($liquidacion->getPagos() as $pago) {
            $total = Decimal::add($total, (string) $pago->getImporte(), 2);
        }
        return $total;
    }

    private function puedeEditarContenido(Liquidacion $liquidacion): bool
    {
        $estadosNoEditables = [
            ConstanteEstadoLiquidacion::APROBADA,
            ConstanteEstadoLiquidacion::PAGADA,
            ConstanteEstadoLiquidacion::ANULADA,
        ];

        $estado = $liquidacion->getEstado();
        if ($estado && in_array($estado->getCodigoInterno(), $estadosNoEditables, true)) {
            return false;
        }

        $relevante = $liquidacion->getPadre() ?? $liquidacion;
        $estadoRelevante = $relevante->getEstado();
        if (!$estadoRelevante) {
            return true;
        }

        return $estadoRelevante->getCodigoInterno() === ConstanteEstadoLiquidacion::BORRADOR;
    }

    private function formatMoney($valor): string
    {
        $valor = (float) $valor;
        return '$' . number_format($valor, 2, ',', '.');
    }

}
