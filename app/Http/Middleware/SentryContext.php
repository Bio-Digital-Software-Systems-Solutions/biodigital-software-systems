<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SentryContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('sentry') && Auth::check()) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                $user = Auth::user();

                $scope->setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->full_name ?? $user->name ?? $user->email,
                ]);

                // Add additional context
                $scope->setContext('user_details', [
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->permissions->pluck('name')->toArray(),
                ]);
            });
        }

        return $next($request);
    }
}
