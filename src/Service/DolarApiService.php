<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DolarApiService
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private CacheInterface $cache;
    
    public function __construct(
        HttpClientInterface $httpClient,
        ParameterBagInterface $params,
        CacheInterface $cache
    ) {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->cache = $cache;
    }
    
    /**
     * Obtiene el valor del dólar blue en tiempo real (con caché de 60 minutos)
     */
    public function getDolarBlue(): ?array
    {
        return $this->cache->get('dolar_blue_data', function (ItemInterface $item) {
            $item->expiresAfter(3600); // 60 minutos = 3600 segundos
            
            try {
                $response = $this->httpClient->request('GET', 'https://monedapi.ar/api/v2/usd/blue');
                
                if ($response->getStatusCode() !== 200) {
                    return null;
                }
                
                $data = $response->toArray();
                
                return [
                    'currency' => $data['currency'] ?? 'USD',
                    'name' => $data['name'] ?? 'Dólar blue',
                    'origin' => $data['origin'] ?? 'BLUE',
                    'buy' => $data['buy'] ?? 0,
                    'sell' => $data['sell'] ?? 0,
                    'updatedAt' => $data['updatedAt'] ?? null,
                    'lastScrapedAt' => $data['lastScrapedAt'] ?? null,
                    'cached' => false,
                    'cached_at' => new \DateTime()
                ];
                
            } catch (\Exception $e) {
                // En caso de error, devolver null
                return null;
            }
        });
    }
    
    /**
     * Obtiene el valor del dólar blue con información de caché
     */
    public function getDolarBlueWithCacheInfo(): ?array
    {
        $data = $this->getDolarBlue();
        
        if ($data === null) {
            return null;
        }
        
        // Verificar si tenemos datos en caché
        $cachedItem = $this->cache->getItem('dolar_blue_data');
        
        if ($cachedItem->isHit()) {
            $data['cached'] = true;
            $data['cached_at'] = $cachedItem->get()['cached_at'] ?? new \DateTime();
            $data['expires_at'] = $cachedItem->getExpiration();
        } else {
            $data['cached'] = false;
        }
        
        return $data;
    }
    
    /**
     * Obtiene el valor de compra del dólar blue
     */
    public function getDolarBlueBuyPrice(): ?float
    {
        $dolarData = $this->getDolarBlue();
        return $dolarData ? $dolarData['buy'] : null;
    }
    
    /**
     * Obtiene el valor de venta del dólar blue
     */
    public function getDolarBlueSellPrice(): ?float
    {
        $dolarData = $this->getDolarBlue();
        return $dolarData ? $dolarData['sell'] : null;
    }
    
    /**
     * Obtiene el valor promedio del dólar blue
     */
    public function getDolarBlueAveragePrice(): ?float
    {
        $dolarData = $this->getDolarBlue();
        if (!$dolarData) {
            return null;
        }
        
        return ($dolarData['buy'] + $dolarData['sell']) / 2;
    }
}
