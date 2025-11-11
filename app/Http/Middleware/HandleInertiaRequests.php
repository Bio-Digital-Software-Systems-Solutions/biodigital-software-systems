<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'cspNonce' => $request->attributes->get('csp_nonce'),
            'csrf_token' => csrf_token(), // Share fresh CSRF token with frontend
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'first_name' => $request->user()->first_name,
                    'last_name' => $request->user()->last_name,
                    'email' => $request->user()->email,
                    'avatar' => $request->user()->avatar,
                    'birth_date' => $request->user()->birth_date?->format('Y-m-d'),
                    'email_verified_at' => $request->user()->email_verified_at,
                    'roles' => $request->user()->roles?->pluck('name') ?? [],
                    'permissions' => $request->user()->getAllPermissions()?->pluck('name') ?? [],
                    'full_name' => $request->user()->full_name,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'unauthorized' => fn () => $request->session()->get('unauthorized'),
            ],
        ];
    }
}
