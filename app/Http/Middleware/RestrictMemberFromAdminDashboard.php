<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictMemberFromAdminDashboard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user only has the Member role and no explicit access permissions
        if ($user && $user->hasRole(Role::MEMBER->value) && $user->roles->count() === 1) {
            $currentRoute = $request->route()->getName();

            // Check if user has explicit permissions for specific routes
            $routePermissionMap = [
                'projects.' => 'view projects',
                'departments.' => 'view departments',
                'student.dashboard' => 'access student dashboard',
                'care-service.' => 'view care service',
            ];

            foreach ($routePermissionMap as $routePrefix => $permission) {
                // If user has the specific permission, allow access
                if ((str_starts_with((string) $currentRoute, $routePrefix) || $currentRoute === $routePrefix) && $user->can($permission)) {
                    return $next($request);
                }
            }

            // If no explicit permission found, redirect to user dashboard
            return redirect()->route('user.dashboard');
        }

        return $next($request);
    }
}
