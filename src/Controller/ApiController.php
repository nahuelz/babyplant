<?php

namespace App\Controller;

use App\Service\DolarApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    private DolarApiService $dolarApiService;
    
    public function __construct(DolarApiService $dolarApiService)
    {
        $this->dolarApiService = $dolarApiService;
    }
    
    /**
     * @Route("/api/dolar-blue", name="api_dolar_blue", methods={"GET"})
     */
    public function getDolarBlue(): JsonResponse
    {
        try {
            $dolarData = $this->dolarApiService->getDolarBlue();
            
            if (!$dolarData) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No se pudo obtener el valor del dólar blue'
                ], 500);
            }
            
            return new JsonResponse([
                'success' => true,
                'data' => $dolarData
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error al obtener el valor del dólar blue: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @Route("/api/dolar-blue/precio-compra", name="api_dolar_blue_buy", methods={"GET"})
     */
    public function getDolarBlueBuyPrice(): JsonResponse
    {
        try {
            $price = $this->dolarApiService->getDolarBlueBuyPrice();
            
            if ($price === null) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No se pudo obtener el precio de compra del dólar blue'
                ], 500);
            }
            
            return new JsonResponse([
                'success' => true,
                'price' => $price,
                'formatted' => '$' . number_format($price, 2, ',', '.')
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error al obtener el precio de compra: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * @Route("/api/dolar-blue/con-cache", name="api_dolar_blue_with_cache", methods={"GET"})
     */
    public function getDolarBlueWithCache(): JsonResponse
    {
        try {
            $dolarData = $this->dolarApiService->getDolarBlueWithCacheInfo();
            
            if (!$dolarData) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'No se pudo obtener el valor del dólar blue'
                ], 500);
            }
            
            return new JsonResponse([
                'success' => true,
                'data' => $dolarData,
                'cache_info' => [
                    'cached' => $dolarData['cached'] ?? false,
                    'cached_at' => isset($dolarData['cached_at']) ? $dolarData['cached_at']->format('Y-m-d H:i:s') : null,
                    'expires_at' => isset($dolarData['expires_at']) ? $dolarData['expires_at']->format('Y-m-d H:i:s') : null
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error al obtener el valor del dólar blue: ' . $e->getMessage()
            ], 500);
        }
    }
}
