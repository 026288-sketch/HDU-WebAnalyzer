<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

/**
 * Class ServiceController
 *
 * Lightweight controller for monitoring microservices in Docker.
 * Process management (start/stop) is delegated to Docker Compose.
 * Here we only check API availability.
 */
class ServiceController extends Controller
{
    /**
     * Service configuration.
     * URLs are retrieved from environment variables (passed via docker-compose).
     */
    private static function getServiceConfig(): array
    {
        return [
            'puppeteer' => [
                'name' => 'Puppeteer Scraper',
                'health_endpoint' => env('PUPPETEER_URL', 'http://puppeteer:3000') . '/health',
            ],
            'python' => [
                'name' => 'Python Similarity',
                'health_endpoint' => env('SIMILARITY_URL', 'http://similarity:8000') . '/health',
            ],
        ];
    }

    private const HEALTH_CHECK_TIMEOUT = 2; // Timeout in seconds

    /**
     * Checks the status of a specific service via HTTP request.
     */
    private static function getServiceStatus(string $serviceName): array
    {
        $serviceConfig = self::getServiceConfig();
        $config = $serviceConfig[$serviceName] ?? null;

        if (! $config) {
            return [
                'status' => 'error',
                'message' => 'Unknown service',
                'response_time' => null,
            ];
        }

        try {
            $start = microtime(true);
            
            // Attempt to reach the service within the Docker network
            $response = Http::timeout(self::HEALTH_CHECK_TIMEOUT)->get($config['health_endpoint']);
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                return [
                    'status' => 'online',
                    'message' => 'Service is running',
                    'response_time' => $responseTime,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Service returned error: ' . $response->status(),
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            // If the service is not responding (e.g., container crashed or restarting)
            return [
                'status' => 'offline',
                'message' => 'Unreachable',
                'response_time' => null,
            ];
        }
    }

    /**
     * Get Puppeteer status.
     */
    public static function getPuppeteerStatus(): array
    {
        return self::getServiceStatus('puppeteer');
    }

    /**
     * Get Python service status.
     */
    public static function getPythonStatus(): array
    {
        return self::getServiceStatus('python');
    }

    /**
     * Get statuses for all services as a list.
     */
    public static function getAllStatuses(): array
    {
        return [
            'puppeteer' => self::getPuppeteerStatus(),
            'python' => self::getPythonStatus(),
        ];
    }

    /**
     * API Endpoint for frontend (AJAX).
     * Returns JSON with statuses.
     */
    public function status(): JsonResponse
    {
        return response()->json(self::getAllStatuses());
    }
}