<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Custom Permission model that extends Spatie Permission with cache invalidation
 * 
 * Automatically clears cache when permissions are created, updated, or deleted
 *
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission withoutRole($roles, $guard = null)
 * @mixin \Eloquent
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