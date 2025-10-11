<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use OpenApi\Attributes as OA;

/**
 * Health Check Controller
 *
 * Provides health check endpoints for monitoring application status
 * @package App\Http\Controllers
 */
class HealthCheckController extends Controller
{
    /**
     * Perform comprehensive health check on all system components
     *
     * This endpoint verifies the health status of critical application components
     * including database, cache, storage, and queue connections. It returns a 200
     * status code if all components are healthy, or 503 if any component fails.
     *
     * @return JsonResponse JSON response with health status and component details
     */
    #[OA\Get(
        path: '/health',
        summary: 'Perform health check',
        description: 'Check the health status of application components (database, cache, storage, queue)',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All systems healthy',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'healthy'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2025-10-11T12:00:00Z'),
                        new OA\Property(
                            property: 'checks',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'database',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'healthy', type: 'boolean', example: true),
                                        new OA\Property(property: 'message', type: 'string', example: 'Database connection successful'),
                                        new OA\Property(property: 'response_time_ms', type: 'number', format: 'float', example: 2.5),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'cache',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'healthy', type: 'boolean', example: true),
                                        new OA\Property(property: 'message', type: 'string', example: 'Cache working correctly'),
                                        new OA\Property(property: 'driver', type: 'string', example: 'redis'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'storage',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'healthy', type: 'boolean', example: true),
                                        new OA\Property(property: 'message', type: 'string', example: 'Storage working correctly'),
                                        new OA\Property(property: 'driver', type: 'string', example: 'local'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'queue',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'healthy', type: 'boolean', example: true),
                                        new OA\Property(property: 'message', type: 'string', example: 'Queue connection successful'),
                                        new OA\Property(property: 'driver', type: 'string', example: 'redis'),
                                        new OA\Property(property: 'pending_jobs', type: 'integer', example: 0),
                                    ]
                                ),
                            ]
                        ),
                        new OA\Property(property: 'environment', type: 'string', example: 'production'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                    ]
                )
            ),
            new OA\Response(
                response: 503,
                description: 'One or more systems unhealthy',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'unhealthy'),
                        new OA\Property(property: 'timestamp', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'checks', type: 'object'),
                        new OA\Property(property: 'environment', type: 'string'),
                        new OA\Property(property: 'version', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        if (! config('monitoring.health_check.enabled')) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Health check is disabled',
            ], 503);
        }

        $checks = [];
        $allHealthy = true;

        // Database check
        if (config('monitoring.health_check.checks.database')) {
            $checks['database'] = $this->checkDatabase();
            $allHealthy = $allHealthy && $checks['database']['healthy'];
        }

        // Cache check
        if (config('monitoring.health_check.checks.cache')) {
            $checks['cache'] = $this->checkCache();
            $allHealthy = $allHealthy && $checks['cache']['healthy'];
        }

        // Storage check
        if (config('monitoring.health_check.checks.storage')) {
            $checks['storage'] = $this->checkStorage();
            $allHealthy = $allHealthy && $checks['storage']['healthy'];
        }

        // Queue check
        if (config('monitoring.health_check.checks.queue')) {
            $checks['queue'] = $this->checkQueue();
            $allHealthy = $allHealthy && $checks['queue']['healthy'];
        }

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'environment' => config('app.env'),
            'version' => config('app.version', '1.0.0'),
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Check database connectivity
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $time = microtime(true);
            DB::select('SELECT 1');
            $duration = (microtime(true) - $time) * 1000;

            return [
                'healthy' => true,
                'message' => 'Database connection successful',
                'response_time_ms' => round($duration, 2),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test';

            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            if ($retrieved === $value) {
                return [
                    'healthy' => true,
                    'message' => 'Cache working correctly',
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'healthy' => false,
                'message' => 'Cache read/write failed',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Cache connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility
     */
    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk(config('filesystems.default'));
            $testFile = 'health_check_' . time() . '.txt';

            $disk->put($testFile, 'test');
            $exists = $disk->exists($testFile);
            $disk->delete($testFile);

            if ($exists) {
                return [
                    'healthy' => true,
                    'message' => 'Storage working correctly',
                    'driver' => config('filesystems.default'),
                ];
            }

            return [
                'healthy' => false,
                'message' => 'Storage read/write failed',
                'driver' => config('filesystems.default'),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Storage access failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connectivity
     */
    protected function checkQueue(): array
    {
        try {
            $size = Queue::size();

            return [
                'healthy' => true,
                'message' => 'Queue connection successful',
                'driver' => config('queue.default'),
                'pending_jobs' => $size,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Queue connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
