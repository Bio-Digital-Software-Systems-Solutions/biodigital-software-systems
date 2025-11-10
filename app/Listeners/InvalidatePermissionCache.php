<?php

namespace App\Listeners;

use App\Services\CacheService;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

/**
 * Event listener for invalidating permission cache
 *
 * Listens to Spatie Permission events and invalidates relevant caches
 */
class InvalidatePermissionCache
{
    /**
     * Handle role attachment events
     */
    public function handleRoleAttached(RoleAttached $event): void
    {
        $this->invalidateCache($event->model);
    }

    /**
     * Handle role detachment events
     */
    public function handleRoleDetached(RoleDetached $event): void
    {
        $this->invalidateCache($event->model);
    }

    /**
     * Handle permission attachment events
     */
    public function handlePermissionAttached(PermissionAttached $event): void
    {
        $this->invalidateCache($event->model);
    }

    /**
     * Handle permission detachment events
     */
    public function handlePermissionDetached(PermissionDetached $event): void
    {
        $this->invalidateCache($event->model);
    }

    /**
     * Invalidate all relevant caches
     */
    private function invalidateCache($model): void
    {
        // Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear our custom caches
        CacheService::forgetPattern('user_management.*');
        CacheService::forgetPattern('roles.*');
        CacheService::forgetPattern('permissions.*');
        CacheService::forgetPattern('users.*');
        CacheService::forgetByTag('permissions');
        CacheService::forgetByTag('roles');

        // Clear user-specific permission caches if it's a user model
        if ($model instanceof \App\Models\User) {
            CacheService::forgetPattern("user_{$model->id}_*");
        }

        \Log::info('Permission cache invalidated via event listener', [
            'model_type' => get_class($model),
            'model_id' => $model->id ?? null,
        ]);
    }
}