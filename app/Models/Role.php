<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Custom Role model that extends Spatie Role with cache invalidation
 *
 * Automatically clears cache when roles are created, updated, or deleted
 */
class Role extends SpatieRole
{
    use ClearsCache;

    /**
     * Cache keys to invalidate when role changes
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

        // Clear specific role-related patterns
        \App\Services\CacheService::forgetPattern('user_*_permissions');
        \App\Services\CacheService::forgetPattern('user_*_roles');
    }
}