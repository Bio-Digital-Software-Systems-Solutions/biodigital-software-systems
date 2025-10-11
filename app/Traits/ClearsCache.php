<?php

namespace App\Traits;

use App\Services\CacheService;

/**
 * Trait for automatic cache clearing based on model
 *
 * Automatically clears model-specific cache when the model is saved or deleted.
 * Cache key is derived from model class name unless explicitly specified.
 *
 * @package App\Traits
 */
trait ClearsCache
{
    /**
     * Clear cache for the current model
     *
     * Automatically determines cache key from model class name.
     * Can be overridden by defining $cacheKey property.
     *
     * @return void
     */
    protected function clearModelCache(): void
    {
        $cacheKey = $this->getCacheKey();
        CacheService::forgetPattern($cacheKey);
    }

    /**
     * Get cache key for this model
     *
     * @return string Cache key pattern
     */
    protected function getCacheKey(): string
    {
        if (property_exists($this, 'cacheKey')) {
            return $this->cacheKey;
        }

        // Extract model name from namespace
        $parts = explode('\\', static::class);
        $modelName = end($parts);

        // Convert to lowercase plural (simple pluralization)
        return strtolower($modelName) . 's';
    }

    /**
     * Boot the trait
     *
     * Automatically clear cache on model events.
     *
     * @return void
     */
    protected static function bootClearsCache(): void
    {
        static::saved(function ($model) {
            $model->clearModelCache();
        });

        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }
}
