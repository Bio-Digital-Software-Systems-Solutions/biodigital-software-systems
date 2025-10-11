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

        // If user only has the Member role, redirect to user dashboard
        if ($user && $user->hasRole(Role::MEMBER->value) && $user->roles->count() === 1) {
            return redirect()->route('user.dashboard');
        }

        return $next($request);
    }
}
