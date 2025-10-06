<?php

namespace App\Controller;

use App\Repository\PedidoProductoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}