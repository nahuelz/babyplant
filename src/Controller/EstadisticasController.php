<?php

namespace App\Controller;

use App\Repository\PedidoProductoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class EstadisticasController extends AbstractController
{
    /**
     * @Route("/estadisticas", name="app_estadisticas")
     * @IsGranted("ROLE_ESTADISTICAS")
     */
    public function index(PedidoProductoRepository $pedidoProductoRepository, Request $request): Response
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

        // Depuración: Mostrar consulta SQL generada
        $query = $pedidoProductoRepository->createQueryBuilder('pp')
            ->select([
                'tv.nombre as producto',
                'SUM(pp.cantidadBandejasPedidas) as cantidad',
                'COUNT(DISTINCT p.id) as total_ventas',
                'tv.id as tipo_variedad_id'
            ])
            ->join('pp.pedido', 'p')
            ->join('pp.tipoVariedad', 'tv')
            ->join('pp.estado', 'e')
            ->where('p.fechaCreacion BETWEEN :fechaInicio AND :fechaFin')
            ->andWhere('e.nombre = :estado')
            ->setParameter('fechaInicio', $fechaInicio->format('Y-m-d 00:00:00'))
            ->setParameter('fechaFin', $fechaFin->format('Y-m-d 23:59:59'))
            ->setParameter('estado', 'ENTREGADO')
            ->groupBy('tv.id, tv.nombre')
            ->orderBy('cantidad', 'DESC')
            ->setMaxResults(50)
            ->getQuery();

        dump('Consulta SQL generada:', $query->getSQL());
        dump('Parámetros de la consulta:', $query->getParameters());
        
        // Depuración: Mostrar resultados obtenidos
        dump('Productos encontrados:', $productos);

        return $this->render('estadisticas/index.html.twig', [
            'productos' => $productos,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ]);
    }
}