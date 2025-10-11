<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (! config('monitoring.enabled')) {
            return;
        }

        $this->setupNewRelic();
        $this->setupDatadog();
        $this->setupCustomMetrics();
    }

    /**
     * Setup New Relic monitoring
     */
    protected function setupNewRelic(): void
    {
        if (! config('monitoring.newrelic.enabled') || ! extension_loaded('newrelic')) {
            return;
        }

        // Set application name
        if (function_exists('newrelic_set_appname')) {
            newrelic_set_appname(config('monitoring.newrelic.app_name'));
        }

        // Add custom attributes
        if (function_exists('newrelic_add_custom_parameter')) {
            foreach (config('monitoring.newrelic.custom_attributes', []) as $key => $value) {
                newrelic_add_custom_parameter($key, $value);
            }
        }

        // Track slow queries
        DB::listen(function (QueryExecuted $query) {
            $threshold = config('monitoring.newrelic.slow_sql_threshold', 0.1) * 1000;

            if ($query->time > $threshold && function_exists('newrelic_record_datastore_segment')) {
                newrelic_record_datastore_segment(function () use ($query) {
                    return [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ];
                });
            }
        });
    }

    /**
     * Setup Datadog monitoring
     */
    protected function setupDatadog(): void
    {
        if (! config('monitoring.datadog.enabled') || ! extension_loaded('ddtrace')) {
            return;
        }

        // Add global tags
        if (function_exists('dd_trace_set_global_tags')) {
            dd_trace_set_global_tags(config('monitoring.datadog.global_tags', []));
        }

        // Set service name
        if (function_exists('dd_trace_set_service_name')) {
            dd_trace_set_service_name(config('monitoring.datadog.service'));
        }
    }

    /**
     * Setup custom metrics tracking
     */
    protected function setupCustomMetrics(): void
    {
        if (! config('monitoring.metrics.enabled')) {
            return;
        }

        // Track slow queries
        if (config('monitoring.metrics.track.queries')) {
            DB::listen(function (QueryExecuted $query) {
                $threshold = config('monitoring.metrics.thresholds.slow_query', 100);

                if ($query->time > $threshold) {
                    Log::channel('monitoring')->warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        // Track request performance
        if (config('monitoring.metrics.track.requests')) {
            Event::listen(RequestHandled::class, function (RequestHandled $event) {
                $duration = microtime(true) - LARAVEL_START;
                $durationMs = $duration * 1000;
                $threshold = config('monitoring.metrics.thresholds.slow_request', 1000);

                if ($durationMs > $threshold) {
                    Log::channel('monitoring')->warning('Slow request detected', [
                        'url' => $event->request->fullUrl(),
                        'method' => $event->request->method(),
                        'duration_ms' => $durationMs,
                        'memory_mb' => memory_get_peak_usage(true) / 1024 / 1024,
                    ]);
                }
            });
        }

        // Track memory usage
        if (config('monitoring.metrics.track.memory')) {
            register_shutdown_function(function () {
                $memoryMB = memory_get_peak_usage(true) / 1024 / 1024;
                $threshold = config('monitoring.metrics.thresholds.high_memory', 128);

                if ($memoryMB > $threshold) {
                    Log::channel('monitoring')->warning('High memory usage detected', [
                        'memory_mb' => $memoryMB,
                        'threshold_mb' => $threshold,
                    ]);
                }
            });
        }
    }
}
