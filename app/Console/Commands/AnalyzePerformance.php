<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class AnalyzePerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analyze:performance
                          {--model= : Specific model to analyze}
                          {--queries : Show slow queries}
                          {--cache : Analyze cache usage}
                          {--memory : Show memory usage}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze application performance and detect N+1 queries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Analyzing application performance...');
        $this->newLine();

        if ($this->option('queries')) {
            $this->analyzeQueries();
        }

        if ($this->option('cache')) {
            $this->analyzeCache();
        }

        if ($this->option('memory')) {
            $this->analyzeMemory();
        }

        if (! $this->option('queries') && ! $this->option('cache') && ! $this->option('memory')) {
            $this->analyzeAll();
        }

        return self::SUCCESS;
    }

    /**
     * Analyze all performance aspects
     */
    protected function analyzeAll(): void
    {
        $this->analyzeModels();
        $this->analyzeQueries();
        $this->analyzeCache();
        $this->analyzeMemory();
    }

    /**
     * Analyze models for potential N+1 issues
     */
    protected function analyzeModels(): void
    {
        $this->info('📁 Analyzing Models for N+1 Risks:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $modelPath = app_path('Models');
        $models = [];

        if (! File::isDirectory($modelPath)) {
            $this->error('Models directory not found!');
            return;
        }

        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\' . str_replace('.php', '', $file->getFilename());

            if (class_exists($className)) {
                try {
                    $model = new $className;
                    $reflection = new \ReflectionClass($model);

                    // Get relationships
                    $relations = [];
                    foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                        if ($method->class === $className &&
                            ! $method->isStatic() &&
                            ! $method->isAbstract() &&
                            strpos($method->name, '__') !== 0
                        ) {
                            $returnType = $method->getReturnType();
                            if ($returnType) {
                                $returnTypeName = $returnType->getName();
                                if (str_contains($returnTypeName, 'Illuminate\\Database\\Eloquent\\Relations\\')) {
                                    $relations[] = $method->name;
                                }
                            }
                        }
                    }

                    if (count($relations) > 0) {
                        $hasEagerLoading = property_exists($model, 'with') && ! empty($model->with);

                        $status = $hasEagerLoading ? '✅' : '⚠️';
                        $this->line(sprintf(
                            '%s %s (%d relations)',
                            $status,
                            $file->getFilename(),
                            count($relations)
                        ));

                        if (! $hasEagerLoading && count($relations) > 2) {
                            $this->warn('  ⚡ Consider adding eager loading: ' . implode(', ', $relations));
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        $this->newLine();
    }

    /**
     * Analyze query performance
     */
    protected function analyzeQueries(): void
    {
        $this->info('🗄️ Query Performance Analysis:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        DB::enableQueryLog();

        // Run a sample query
        $start = microtime(true);
        try {
            // Sample queries to analyze
            DB::table('users')->limit(10)->get();
        } catch (\Exception $e) {
            $this->error('Error running queries: ' . $e->getMessage());
            return;
        }
        $duration = (microtime(true) - $start) * 1000;

        $queries = DB::getQueryLog();

        $this->info(sprintf('Total queries executed: %d', count($queries)));
        $this->info(sprintf('Total execution time: %.2fms', $duration));

        if (count($queries) > 10) {
            $this->warn('⚠️ High number of queries detected. Consider optimizing with eager loading.');
        }

        $this->newLine();

        // Show slow queries
        $slowQueries = array_filter($queries, fn($query) => $query['time'] > 100);

        if (count($slowQueries) > 0) {
            $this->warn('🐌 Slow Queries (>100ms):');
            foreach ($slowQueries as $query) {
                $this->line(sprintf('  • %.2fms - %s', $query['time'], substr($query['query'], 0, 80)));
            }
        }

        DB::disableQueryLog();
        $this->newLine();
    }

    /**
     * Analyze cache usage
     */
    protected function analyzeCache(): void
    {
        $this->info('💾 Cache Configuration:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $cacheDriver = config('cache.default');
        $this->info('Current driver: ' . $cacheDriver);

        if ($cacheDriver === 'redis') {
            $this->line('✅ Using Redis (Optimal for production)');
        } elseif ($cacheDriver === 'database') {
            $this->warn('⚠️ Using database cache (Consider switching to Redis)');
        } elseif ($cacheDriver === 'file') {
            $this->warn('⚠️ Using file cache (Consider switching to Redis)');
        }

        $this->newLine();
    }

    /**
     * Analyze memory usage
     */
    protected function analyzeMemory(): void
    {
        $this->info('🧠 Memory Usage:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;
        $memoryLimit = ini_get('memory_limit');

        $this->info(sprintf('Current usage: %.2f MB', $memoryUsage));
        $this->info(sprintf('Peak usage: %.2f MB', $memoryPeak));
        $this->info('Memory limit: ' . $memoryLimit);

        if ($memoryPeak > 128) {
            $this->warn('⚠️ High memory usage detected. Consider optimizing queries and using chunking.');
        }

        $this->newLine();
    }
}
