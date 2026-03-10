<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class SentryTestController extends Controller
{
    /**
     * Test Sentry error tracking.
     *
     * This endpoint is only available in local/development environments
     * and can be used to verify that Sentry is properly configured.
     *
     * WARNING: This endpoint should NEVER be available in production!
     */
    public function testError(): void
    {
        // Only allow in local/development environments
        if (! app()->environment(['local', 'development'])) {
            abort(404);
        }

        // Log a message before throwing the exception
        Log::info('Testing Sentry error reporting', [
            'user_id' => auth()->id(),
            'environment' => app()->environment(),
        ]);

        // Throw a test exception that will be captured by Sentry
        throw new \Exception('This is a test exception to verify Sentry integration is working correctly.');
    }

    /**
     * Test Sentry message capture.
     *
     * This endpoint sends a test message to Sentry without throwing an exception.
     */
    public function testMessage()
    {
        // Only allow in local/development environments
        if (! app()->environment(['local', 'development'])) {
            abort(404);
        }

        if (app()->bound('sentry')) {
            \Sentry\captureMessage('This is a test message from Sentry Laravel integration', \Sentry\Severity::info());

            return response()->json([
                'success' => true,
                'message' => 'Test message sent to Sentry successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Sentry is not configured or DSN is missing.',
        ], 500);
    }

    /**
     * Test Sentry breadcrumbs and context.
     */
    public function testBreadcrumbs()
    {
        // Only allow in local/development environments
        if (! app()->environment(['local', 'development'])) {
            abort(404);
        }

        if (app()->bound('sentry')) {
            // Add custom breadcrumbs
            \Sentry\addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_USER,
                    'test',
                    'User initiated breadcrumb test'
                )
            );

            \Sentry\addBreadcrumb(
                new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_INFO,
                    \Sentry\Breadcrumb::TYPE_NAVIGATION,
                    'navigation',
                    'Navigated to test breadcrumbs page'
                )
            );

            // Add custom context
            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                $scope->setContext('test_context', [
                    'test_key' => 'test_value',
                    'timestamp' => now()->toIso8601String(),
                    'environment' => app()->environment(),
                ]);
            });

            // Throw an exception to capture all breadcrumbs and context
            throw new \Exception('Test exception with breadcrumbs and custom context');
        }

        return response()->json([
            'success' => false,
            'message' => 'Sentry is not configured or DSN is missing.',
        ], 500);
    }
}
