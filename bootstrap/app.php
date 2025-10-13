<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Event;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SentryContext::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Exclude TUS upload endpoints from CSRF verification
        // TUS protocol uses custom headers and multiple request types
        $middleware->validateCsrfTokens(except: [
            'api/files',
            'api/files/*',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'restrict.member' => \App\Http\Middleware\RestrictMemberFromAdminDashboard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Integrate Sentry for error reporting
        $exceptions->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response) {
            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'error' => 'La page a expiré, veuillez réessayer.',
                ]);
            }

            // Handle 403 errors with a flash message instead of redirecting
            if ($response->getStatusCode() === 403) {
                return back()->with([
                    'unauthorized' => now()->timestamp,
                ]);
            }

            // For other errors, redirect to error page
            if (in_array($response->getStatusCode(), [500, 503, 404])) {
                return inertia('Error', ['status' => $response->getStatusCode()])
                    ->toResponse(request())
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Send quiz deadline reminders daily at 9 AM
        $schedule->command('quiz:send-deadline-reminders')
            ->dailyAt('09:00')
            ->timezone('Europe/Paris');
    })
    ->booting(function () {
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \App\Listeners\UpdateLoginInformation::class,
        );
    })
    ->create();
