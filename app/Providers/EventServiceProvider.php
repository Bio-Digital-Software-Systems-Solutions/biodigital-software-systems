<?php

namespace App\Providers;

use App\Listeners\InvalidatePermissionCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        RoleAttached::class => [
            InvalidatePermissionCache::class . '@handleRoleAttached',
        ],
        RoleDetached::class => [
            InvalidatePermissionCache::class . '@handleRoleDetached',
        ],
        PermissionAttached::class => [
            InvalidatePermissionCache::class . '@handlePermissionAttached',
        ],
        PermissionDetached::class => [
            InvalidatePermissionCache::class . '@handlePermissionDetached',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}