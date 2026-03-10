<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Custom Role model that extends Spatie Role with cache invalidation
 * 
 * Automatically clears cache when roles are created, updated, or deleted
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 * @mixin \Eloquent
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