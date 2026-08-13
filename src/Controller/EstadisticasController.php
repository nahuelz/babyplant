<?php

namespace App\Controller;

use App\Repository\EntregaRepository;
use App\Repository\PedidoProductoRepository;
use App\Repository\PedidoRepository;
use App\Repository\RemitoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/estadisticas")
 * @IsGranted("ROLE_ESTADISTICAS")
 */

class EstadisticasController extends AbstractController
{
    /**
     * Dashboard general de Estadísticas.
     *
     * Vista general del sistema: pedidos, remitos, clientes.
     *
     * Implementado hasta el momento:
     * - Pedidos nuevos registrados: hoy, semana actual (desde el lunes) y mes actual,
     *   con detalle (producto, cantidad de bandejas, cliente) al hacer clic en cada KPI.
     * - Entregas nuevas registradas: hoy, semana actual y mes actual, con el mismo
     *   detalle (producto, cantidad de bandejas, cliente) al hacer clic en cada KPI.
     * - Últimas tareas realizadas sobre pedidos: últimos cambios de estado
     *   (EstadoPedidoProductoHistorico), con producto, estado, cliente y usuario responsable.
     *
     * Funcionalidad futura prevista para esta vista:
     * - KPIs generales: pedidos activos, remitos del mes,
     *   deuda total de clientes (cuenta corriente), producción del mes.
     * - Gráfico de entregas por día (últimos 30 días).
     * - Gráfico de estado de pedidos (pendiente / entregado / cancelado).
     * - Top clientes por volumen de entregas o facturación del período.
     * - Productos más entregados (resumen).
     * - Alertas / accesos rápidos: pedidos atrasados, gastos del mes, liquidaciones pendientes.
     *
     * @Route("/", name="estadisticas_dashboard")
     */
    public function dashboard(
        PedidoRepository $pedidoRepository,
        PedidoProductoRepository $pedidoProductoRepository,
        EntregaRepository $entregaRepository,
        RemitoRepository $remitoRepository
    ): Response
    {
        [, $desdeHoy, $hastaHoy] = $this->getRangoPeriodo('hoy');
        [, $desdeSemana, $hastaSemana] = $this->getRangoPeriodo('semana');
        [, $desdeMes, $hastaMes] = $this->getRangoPeriodo('mes');

        return $this->render('estadisticas/dashboard.html.twig', [
            'pedidos_hoy' => $pedidoRepository->contarPedidosNuevos($desdeHoy, $hastaHoy),
            'pedidos_semana' => $pedidoRepository->contarPedidosNuevos($desdeSemana, $hastaSemana),
            'pedidos_mes' => $pedidoRepository->contarPedidosNuevos($desdeMes, $hastaMes),
            'entregas_hoy' => $entregaRepository->contarEntregasNuevas($desdeHoy, $hastaHoy),
            'entregas_semana' => $entregaRepository->contarEntregasNuevas($desdeSemana, $hastaSemana),
            'entregas_mes' => $entregaRepository->contarEntregasNuevas($desdeMes, $hastaMes),
            'remitos_hoy' => $remitoRepository->contarRemitosNuevos($desdeHoy, $hastaHoy),
            'remitos_semana' => $remitoRepository->contarRemitosNuevos($desdeSemana, $hastaSemana),
            'remitos_mes' => $remitoRepository->contarRemitosNuevos($desdeMes, $hastaMes),
            'ultimas_tareas' => $pedidoProductoRepository->getUltimasTareasPedidos(10),
        ]);
    }

    /**
     * Detalle de pedidos nuevos registrados según el período seleccionado (hoy, semana o mes).
     * Se usa para poblar el modal que se abre al hacer clic en los KPIs del dashboard.
     *
     * @Route("/pedidos-nuevos-detalle", name="estadisticas_pedidos_nuevos_detalle")
     */
    public function pedidosNuevosDetalle(Request $request, PedidoProductoRepository $pedidoProductoRepository): Response
    {
        $periodo = $request->query->get('periodo', 'hoy');
        $pagina = max(1, (int) $request->query->get('pagina', 1));
        $limite = 10;
        $offset = ($pagina - 1) * $limite;

        [$titulo, $desde, $hasta] = $this->getRangoPeriodo($periodo);

        $detalle = $pedidoProductoRepository->getPedidosNuevosDetalle($desde, $hasta, $limite, $offset);
        $total = $pedidoProductoRepository->contarPedidosNuevosDetalle($desde, $hasta);
        $totalPaginas = (int) ceil($total / $limite);

        $html = $this->renderView('estadisticas/_pedidos_nuevos_detalle.html.twig', [
            'detalle' => $detalle,
            'periodo' => $periodo,
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
        ]);

        return new JsonResponse([
            'titulo' => $titulo,
            'html' => $html,
        ]);
    }

    /**
     * Detalle de entregas nuevas registradas según el período seleccionado (hoy, semana o mes).
     * Se usa para poblar el panel que se abre al hacer clic en los KPIs de entregas del dashboard.
     *
     * @Route("/entregas-nuevas-detalle", name="estadisticas_entregas_nuevas_detalle")
     */
    public function entregasNuevasDetalle(Request $request, EntregaRepository $entregaRepository): Response
    {
        $periodo = $request->query->get('periodo', 'hoy');
        $pagina = max(1, (int) $request->query->get('pagina', 1));
        $limite = 10;
        $offset = ($pagina - 1) * $limite;

        [, $desde, $hasta] = $this->getRangoPeriodo($periodo);
        $titulo = $this->getTituloPeriodo('Entregas nuevas', $periodo);

        $detalle = $entregaRepository->getEntregasNuevasDetalle($desde, $hasta, $limite, $offset);
        $total = $entregaRepository->contarEntregasNuevasDetalle($desde, $hasta);
        $totalPaginas = (int) ceil($total / $limite);

        $html = $this->renderView('estadisticas/_entregas_nuevas_detalle.html.twig', [
            'detalle' => $detalle,
            'periodo' => $periodo,
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
        ]);

        return new JsonResponse([
            'titulo' => $titulo,
            'html' => $html,
        ]);
    }

    /**
     * Detalle de remitos nuevos registrados según el período seleccionado (hoy, semana o mes).
     * Se usa para poblar el panel que se abre al hacer clic en los KPIs de remitos del dashboard.
     *
     * @Route("/remitos-nuevos-detalle", name="estadisticas_remitos_nuevos_detalle")
     */
    public function remitosNuevosDetalle(Request $request, RemitoRepository $remitoRepository): Response
    {
        $periodo = $request->query->get('periodo', 'hoy');
        $pagina = max(1, (int) $request->query->get('pagina', 1));
        $limite = 10;
        $offset = ($pagina - 1) * $limite;

        [, $desde, $hasta] = $this->getRangoPeriodo($periodo);
        $titulo = $this->getTituloPeriodo('Remitos nuevos', $periodo);

        $detalle = $remitoRepository->getRemitosNuevosDetalle($desde, $hasta, $limite, $offset);
        $total = $remitoRepository->contarRemitosNuevosDetalle($desde, $hasta);
        $totalPaginas = (int) ceil($total / $limite);

        $html = $this->renderView('estadisticas/_remitos_nuevos_detalle.html.twig', [
            'detalle' => $detalle,
            'periodo' => $periodo,
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
        ]);

        return new JsonResponse([
            'titulo' => $titulo,
            'html' => $html,
        ]);
    }

    /**
     * Calcula el rango de fechas (desde / hasta) para los períodos usados en el
     * dashboard: 'hoy', 'semana' (semana actual desde el lunes) y 'mes' (mes
     * actual desde el día 1). El título devuelto corresponde a "Pedidos nuevos";
     * para otras secciones usar getTituloPeriodo().
     *
     * @return array{0: string, 1: \DateTime, 2: \DateTime}
     */
    private function getRangoPeriodo(string $periodo): array
    {
        $hoy = new \DateTime();

        switch ($periodo) {
            case 'semana':
                $desde = (clone $hoy)->modify('monday this week');
                $titulo = $this->getTituloPeriodo('Pedidos nuevos', $periodo);
                break;
            case 'mes':
                $desde = (clone $hoy)->modify('first day of this month');
                $titulo = $this->getTituloPeriodo('Pedidos nuevos', $periodo);
                break;
            default:
                $desde = clone $hoy;
                $titulo = $this->getTituloPeriodo('Pedidos nuevos', $periodo);
                break;
        }

        return [$titulo, $desde, clone $hoy];
    }

    /**
     * Construye el título a mostrar según la sección (ej: 'Pedidos nuevos',
     * 'Entregas nuevas') y el período ('hoy', 'semana', 'mes').
     */
    private function getTituloPeriodo(string $etiqueta, string $periodo): string
    {
        switch ($periodo) {
            case 'semana':
                return $etiqueta . ' - Esta semana';
            case 'mes':
                return $etiqueta . ' - Este mes';
            default:
                return $etiqueta . ' - Hoy';
        }
    }

    /**
     * @Route("/entregas", name="estadisticas_entregas")
     */
    public function entregas(PedidoProductoRepository $pedidoProductoRepository, Request $request): Response
    {
        // Obtener fechas del request o usar valores por defecto (últimos 30 días)
        $fechaFin = new \DateTime();
        $fechaInicio = (clone $fechaFin)->modify('-30 days');

        // Si se enviaron fechas en el request, usarlas
        $fechaInicioStr = $request->query->get('fecha_inicio') ?? $request->query->get('fecha_inicio_display');
        $fechaFinStr = $request->query->get('fecha_fin') ?? $request->query->get('fecha_fin_display');
        
        if ($fechaInicioStr && $fechaFinStr) {
            try {
                // Convertir fechas del formato dd/mm/yyyy a DateTime
                $fechaInicio = \DateTime::createFromFormat('d/m/Y', $fechaInicioStr);
                $fechaFin = \DateTime::createFromFormat('d/m/Y', $fechaFinStr);
                
                if ($fechaInicio === false || $fechaFin === false) {
                    throw new \Exception('Formato de fecha inválido');
                }
                
                // Asegurar que la fecha de inicio no sea mayor a la de fin
                if ($fechaInicio > $fechaFin) {
                    $temp = $fechaInicio;
                    $fechaInicio = $fechaFin;
                    $fechaFin = $temp;
                }
                
                // Asegurar que las fechas tengan la hora correcta
                $fechaInicio->setTime(0, 0, 0);
                $fechaFin->setTime(23, 59, 59);
                
            } catch (\Exception $e) {
                // En caso de error en el formato de fechas, usar valores por defecto
                $this->addFlash('error', 'Formato de fechas inválido. Mostrando últimos 30 días.');
                $fechaInicio = (new \DateTime())->modify('-30 days');
                $fechaFin = new \DateTime();
            }
        }

        // Obtener productos más vendidos en el rango de fechas
        $productos = $pedidoProductoRepository->getProductosMasVendidos(
            $fechaInicio,
            $fechaFin,
            50 // Límite de resultados
        );

        return $this->render('estadisticas/entregas.html.twig', [
            'productos' => $productos,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
    }

    /**
     * @Route("/remitos", name="estadisticas_remitos")
     */
    public function remitos(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Obtener fechas del request o usar valores por defecto (últimos 30 días)
        $fechaFin = new \DateTime();
        $fechaInicio = (clone $fechaFin)->modify('-30 days');

        // Si se enviaron fechas en el request, usarlas
        $fechaInicioStr = $request->query->get('fecha_inicio') ?? $request->query->get('fecha_inicio_display');
        $fechaFinStr = $request->query->get('fecha_fin') ?? $request->query->get('fecha_fin_display');

        if ($fechaInicioStr && $fechaFinStr) {
            try {
                // Convertir fechas del formato dd/mm/yyyy a DateTime
                $fechaInicio = \DateTime::createFromFormat('d/m/Y', $fechaInicioStr);
                $fechaFin = \DateTime::createFromFormat('d/m/Y', $fechaFinStr);

                if ($fechaInicio === false || $fechaFin === false) {
                    throw new \Exception('Formato de fecha inválido');
                }

                // Asegurar que la fecha de inicio no sea mayor a la de fin
                if ($fechaInicio > $fechaFin) {
                    $temp = $fechaInicio;
                    $fechaInicio = $fechaFin;
                    $fechaFin = $temp;
                }

                // Asegurar que las fechas tengan la hora correcta
                $fechaInicio->setTime(0, 0, 0);
                $fechaFin->setTime(23, 59, 59);

            } catch (\Exception $e) {
                // En caso de error en el formato de fechas, usar valores por defecto
                $this->addFlash('error', 'Formato de fechas inválido. Mostrando últimos 30 días.');
                $fechaInicio = (new \DateTime())->modify('-30 days');
                $fechaFin = new \DateTime();
            }
        }

        // Obtener estadísticas de remitos usando consulta nativa para mejor compatibilidad
        $conn = $entityManager->getConnection();
        $sql = '
            SELECT 
                DATE(r.fecha_creacion) as fecha,
                COUNT(r.id) as cantidad,
                SUM(COALESCE(r.total_deuda, 0)) as monto_total
            FROM remito r
            WHERE r.fecha_creacion BETWEEN :fechaInicio AND :fechaFin
            GROUP BY DATE(r.fecha_creacion)
            ORDER BY fecha ASC
        ';
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'fechaInicio' => $fechaInicio->format('Y-m-d 00:00:00'),
            'fechaFin' => $fechaFin->format('Y-m-d 23:59:59')
        ]);
        
        $estadisticas = $result->fetchAllAssociative();

        // Formatear datos para el gráfico
        $datosGrafico = [
            'fechas' => [],
            'cantidades' => [],
            'montos' => []
        ];

        foreach ($estadisticas as $estadistica) {
            $fecha = new \DateTime($estadistica['fecha']);
            $datosGrafico['fechas'][] = $fecha->format('d/m/Y');
            $datosGrafico['cantidades'][] = (int) $estadistica['cantidad'];
            $datosGrafico['montos'][] = (float) $estadistica['monto_total'];
            
            // Asegurarse de que la fecha esté como objeto DateTime para la vista
            $estadistica['fecha'] = $fecha;
        }

        return $this->render('estadisticas/remitos.html.twig', [
            'estadisticas' => $estadisticas,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'datos_grafico' => $datosGrafico,
            'total_remitos' => array_sum($datosGrafico['cantidades']),
            'monto_total' => array_sum($datosGrafico['montos'])
        ]);
    }

    /**
     * @Route("/pedidos-por-cliente", name="estadisticas_pedidos_cliente")
     */
    public function pedidosPorCliente(
        Request $request,
        PedidoRepository $pedidoRepository
    )
    {
        // Obtener fechas del request o usar valores por defecto (últimos 30 días)
        $fechaFin = new \DateTime();
        $fechaInicio = (clone $fechaFin)->modify('-30 days');

        // Si se enviaron fechas en el request, usarlas
        $fechaInicioStr = $request->query->get('fecha_inicio') ?? $request->query->get('fecha_inicio');
        $fechaFinStr = $request->query->get('fecha_fin') ?? $request->query->get('fecha_fin');

        if ($fechaInicioStr && $fechaFinStr) {
            try {
                // Convertir fechas del formato dd/mm/yyyy a DateTime
                $fechaInicio = \DateTime::createFromFormat('d/m/Y', $fechaInicioStr);
                $fechaFin = \DateTime::createFromFormat('d/m/Y', $fechaFinStr);

                if ($fechaInicio === false || $fechaFin === false) {
                    throw new \Exception('Formato de fecha inválido');
                }

                // Asegurar que la fecha de inicio no sea mayor a la de fin
                if ($fechaInicio > $fechaFin) {
                    $temp = $fechaInicio;
                    $fechaInicio = $fechaFin;
                    $fechaFin = $temp;
                }

                // Asegurar que las fechas tengan la hora correcta
                $fechaInicio->setTime(0, 0, 0);
                $fechaFin->setTime(23, 59, 59);

            } catch (\Exception $e) {
                // En caso de error en el formato de fechas, usar valores por defecto
                $this->addFlash('error', 'Formato de fechas inválido. Mostrando últimos 30 días.');
                $fechaInicio = (new \DateTime())->modify('-30 days');
                $fechaFin = new \DateTime();
            }
        }

        // Paginación
        $pagina = $request->query->get('pagina', 1);
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        $resultados = $pedidoRepository->getPedidosPorClientePaginado($fechaInicio, $fechaFin, $limite, $offset);

        // Obtener total para paginación
        $totalResultados = $pedidoRepository->getTotalPedidosPorCliente($fechaInicio, $fechaFin);
        $totalPaginas = ceil($totalResultados / $limite);

        return $this->render('estadisticas/pedidos_por_cliente.html.twig', [
            'resultados' => $resultados,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'pagina_actual' => $pagina,
            'total_paginas' => $totalPaginas,
            'total_resultados' => $totalResultados,
        ]);
    }

    /**
     * @Route("/produccion-por-producto", name="estadisticas_produccion_producto")
     */
    public function produccionPorProducto(
        Request $request,
        PedidoProductoRepository $pedidoProductoRepository
    ) {
        $desde = $request->query->get('desde')
            ? new \DateTime($request->query->get('desde'))
            : new \DateTime('first day of this month');

        $hasta = $request->query->get('hasta')
            ? new \DateTime($request->query->get('hasta'))
            : new \DateTime();

        $resultados = $pedidoProductoRepository->getProduccionPorProducto($desde, $hasta);

        return $this->render('estadisticas/produccion_por_producto.html.twig', [
            'resultados' => $resultados,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

}