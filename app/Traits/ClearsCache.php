<?php

namespace App\Traits;

use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

/**
 * Trait for automatic cache clearing based on model
 *
 * Automatically clears model-specific cache when the model is saved or deleted.
 * Cache key is derived from model class name unless explicitly specified.
 * Supports clearing related model caches and custom cache invalidation logic.
 *
 * @package App\Traits
 */
trait ClearsCache
{
    /**
     * Clear cache for the current model and related models
     *
     * @return void
     */
    protected function clearModelCache(): void
    {
        // Clear primary model cache
        $cacheKey = $this->getCacheKey();
        CacheService::forgetPattern($cacheKey . '.*');

        // Clear related model caches if defined
        $this->clearRelatedCaches();

        // Execute custom cache invalidation logic if defined
        if (method_exists($this, 'customCacheInvalidation')) {
            $this->customCacheInvalidation();
        }

        Log::info("Cache invalidated for model: " . static::class . " (ID: " . ($this->id ?? 'new') . ")");
    }

    /**
     * Clear caches for related models
     *
     * Override this method in your model to define which related caches should be cleared
     * Example:
     * protected function clearRelatedCaches(): void
     * {
     *     CacheService::forgetPattern('events.*');  // Clear events when user is updated
     *     CacheService::forgetPattern('books.*');   // Clear books when user is updated
     * }
     *
     * @return void
     */
    protected function clearRelatedCaches(): void
    {
        if (property_exists($this, 'relatedCacheKeys') && is_array($this->relatedCacheKeys)) {
            foreach ($this->relatedCacheKeys as $pattern) {
                CacheService::forgetPattern($pattern);
            }
        }
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

        // Convert to lowercase with proper pluralization
        return $this->pluralize(strtolower($modelName));
    }

    /**
     * Simple pluralization for cache keys
     *
     * @param string $word
     * @return string
     */
    protected function pluralize(string $word): string
    {
        // Handle common irregular plurals
        $irregulars = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'tooth' => 'teeth',
            'foot' => 'feet',
            'mouse' => 'mice',
            'goose' => 'geese',
        ];

        if (isset($irregulars[$word])) {
            return $irregulars[$word];
        }

        // Handle words ending in 'y'
        if (str_ends_with($word, 'y') && !in_array(substr($word, -2, 1), ['a', 'e', 'i', 'o', 'u'])) {
            return substr($word, 0, -1) . 'ies';
        }

        // Handle words ending in s, sh, ch, x, z
        foreach (['s', 'sh', 'ch', 'x', 'z'] as $ending) {
            if (str_ends_with($word, $ending)) {
                return $word . 'es';
            }
        }

        // Default: just add 's'
        return $word . 's';
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
        // Clear cache after creating
        static::created(function ($model) {
            $model->clearModelCache();
        });

        // Clear cache after updating
        static::updated(function ($model) {
            $model->clearModelCache();
        });

        // Clear cache after deleting
        static::deleted(function ($model) {
            $model->clearModelCache();
        });

        // Clear cache when restoring soft-deleted models (only if SoftDeletes trait is used)
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $model->clearModelCache();
            });
        }
    }
}
