<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache duration constants (in seconds)
     */
    const SHORT_CACHE = 300;      // 5 minutes
    const MEDIUM_CACHE = 3600;    // 1 hour
    const LONG_CACHE = 86400;     // 24 hours

    /**
     * Remember a value in cache with automatic key generation
     *
     * @param string $key
     * @param callable $callback
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = self::MEDIUM_CACHE)
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage());
            // Fallback: execute callback directly if cache fails
            return $callback();
        }
    }

    /**
     * Remember paginated data
     */
    public static function rememberPaginated(string $baseKey, int $page, callable $callback, int $ttl = self::SHORT_CACHE)
    {
        $key = "{$baseKey}.page.{$page}";
        return self::remember($key, $callback, $ttl);
    }

    /**
     * Forget cache keys matching a pattern
     */
    public static function forgetPattern(string $pattern): void
    {
        try {
            $keys = Cache::get('cache_keys', []);
            $matchingKeys = array_filter($keys, fn($key) => str_contains($key, $pattern));
            
            foreach ($matchingKeys as $key) {
                Cache::forget($key);
            }
        } catch (\Exception $e) {
            Log::error('Cache forget error: ' . $e->getMessage());
        }
    }

    /**
     * Forget cache by tag (requires Redis/Memcached)
     */
    public static function forgetByTag(string $tag): void
    {
        try {
            Cache::tags([$tag])->flush();
        } catch (\Exception $e) {
            Log::warning('Cache tags not supported, falling back to pattern matching');
            self::forgetPattern($tag);
        }
    }

    /**
     * Generate a cache key for user-specific data
     */
    public static function userKey(string $base, int $userId): string
    {
        return "{$base}.user.{$userId}";
    }

    /**
     * Generate a cache key for role/permission data
     */
    public static function roleKey(string $base, string $roleOrPermission): string
    {
        return "{$base}.role.{$roleOrPermission}";
    }
}
