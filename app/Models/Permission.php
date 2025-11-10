<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Custom Permission model that extends Spatie Permission with cache invalidation
 *
 * Automatically clears cache when permissions are created, updated, or deleted
 */
class Permission extends SpatiePermission
{
    use ClearsCache;

    /**
     * Cache keys to invalidate when permission changes
     *
     * @var array
     */
    protected $relatedCacheKeys = [
        'user_management.*',
        'roles.*',
        'permissions.*',
        'users.*',
        'role_permissions.*',
    ];

    /**
     * Custom cache invalidation logic
     *
     * @return void
     */
    public function customCacheInvalidation(): void
    {
        // Clear all user-related permissions cache
        \App\Services\CacheService::forgetByTag('permissions');
        \App\Services\CacheService::forgetByTag('roles');

        // Clear specific permission-related patterns
        \App\Services\CacheService::forgetPattern('user_*_permissions');
        \App\Services\CacheService::forgetPattern('user_*_roles');
    }
}