<?php

namespace App\Services\WooCommerce;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WooCommerceClient
{
    protected string $storeUrl;
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $apiVersion = 'wc/v3';
    protected int $timeout = 30;
    protected int $retryTimes = 3;
    protected int $retryDelay = 1000; // milliseconds

    public function __construct()
    {
        $this->storeUrl = rtrim(config('woocommerce.store_url'), '/');
        $this->consumerKey = config('woocommerce.consumer_key');
        $this->consumerSecret = config('woocommerce.consumer_secret');
    }

    /**
     * Make a GET request to WooCommerce API
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Make a POST request to WooCommerce API
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    /**
     * Make a PUT request to WooCommerce API
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, [], $data);
    }

    /**
     * Make a DELETE request to WooCommerce API
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Make a PATCH request to WooCommerce API
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $endpoint, [], $data);
    }

    /**
     * Batch update/create/delete items
     */
    public function batch(string $endpoint, array $data): array
    {
        return $this->post($endpoint . '/batch', $data);
    }

    /**
     * Get all items with pagination handling
     */
    public function getAll(string $endpoint, array $params = []): array
    {
        $allItems = [];
        $page = 1;
        $perPage = 100;

        do {
            $params['page'] = $page;
            $params['per_page'] = $perPage;

            $response = $this->get($endpoint, $params);
            
            if (!isset($response['success']) || !$response['success']) {
                break;
            }

            $items = $response['data'] ?? [];
            $allItems = array_merge($allItems, $items);

            // Check if there are more pages
            $totalPages = (int) ($response['headers']['X-WP-TotalPages'] ?? 1);
            $page++;

        } while ($page <= $totalPages);

        return [
            'success' => true,
            'data' => $allItems,
            'total' => count($allItems)
        ];
    }

    /**
     * Test connection to WooCommerce
     */
    public function testConnection(): array
    {
        try {
            $response = $this->get('system_status');
            
            return [
                'success' => true,
                'message' => 'Successfully connected to WooCommerce',
                'data' => $response['data'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to WooCommerce: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get WooCommerce store information
     */
    public function getStoreInfo(): array
    {
        $cacheKey = 'woocommerce_store_info';
        
        return Cache::remember($cacheKey, 3600, function () {
            try {
                $response = $this->get('system_status');
                
                if ($response['success']) {
                    return [
                        'success' => true,
                        'data' => [
                            'version' => $response['data']['environment']['version'] ?? 'Unknown',
                            'permalink_structure' => $response['data']['environment']['permalink_structure'] ?? '',
                            'currency' => $response['data']['settings']['currency'] ?? 'USD',
                        ]
                    ];
                }
                
                return $response;
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Make HTTP request to WooCommerce API
     */
    protected function request(string $method, string $endpoint, array $params = [], array $data = []): array
    {
        try {
            $url = $this->buildUrl($endpoint);
            
            Log::info("WooCommerce API Request", [
                'method' => $method,
                'url' => $url,
                'params' => $params,
                'data' => $data
            ]);

            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay)
                ->withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->$method($url, $method === 'GET' ? $params : $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'headers' => $response->headers(),
                    'status' => $response->status()
                ];
            }

            // Handle error response
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Unknown error occurred';

            Log::error("WooCommerce API Error", [
                'method' => $method,
                'url' => $url,
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'status' => $response->status(),
                'data' => $errorData
            ];

        } catch (\Exception $e) {
            Log::error("WooCommerce API Exception", [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'exception' => get_class($e)
            ];
        }
    }

    /**
     * Build full API URL
     */
    protected function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');
        return "{$this->storeUrl}/wp-json/{$this->apiVersion}/{$endpoint}";
    }

    /**
     * Set custom timeout
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set retry configuration
     */
    public function setRetry(int $times, int $delayMs): self
    {
        $this->retryTimes = $times;
        $this->retryDelay = $delayMs;
        return $this;
    }

    /**
     * Clear cached data
     */
    public function clearCache(): void
    {
        Cache::forget('woocommerce_store_info');
    }
}