<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

class HealthCheckController extends Controller
{
    /**
     * Perform health check
     */
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
