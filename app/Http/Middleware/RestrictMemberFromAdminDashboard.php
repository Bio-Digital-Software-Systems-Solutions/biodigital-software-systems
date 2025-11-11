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
                'pastoral-care.' => 'view pastoral care',
            ];

            foreach ($routePermissionMap as $routePrefix => $permission) {
                if (str_starts_with($currentRoute, $routePrefix) || $currentRoute === $routePrefix) {
                    // If user has the specific permission, allow access
                    if ($user->can($permission)) {
                        return $next($request);
                    }
                }
            }

            // If no explicit permission found, redirect to user dashboard
            return redirect()->route('user.dashboard');
        }

        return $next($request);
    }
}
