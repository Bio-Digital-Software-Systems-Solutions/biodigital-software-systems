<?php

namespace App\Traits;

use Illuminate\Http\RedirectResponse;

/**
 * Trait for standardized flash messages
 *
 * Provides consistent methods for sending flash messages with redirects.
 * Supports success, error, info, and warning message types.
 *
 * @package App\Traits
 */
trait HasFlashMessages
{
    /**
     * Redirect with success message
     *
     * @param string $route Route name
     * @param string $message Success message
     * @param array<string, mixed> $parameters Route parameters
     * @return RedirectResponse
     */
    protected function redirectWithSuccess(
        string $route,
        string $message,
        array $parameters = []
    ): RedirectResponse {
        return redirect()
            ->route($route, $parameters)
            ->with('message', $message);
    }

    /**
     * Redirect with error message
     *
     * @param string $route Route name
     * @param string $message Error message
     * @param array<string, mixed> $parameters Route parameters
     * @return RedirectResponse
     */
    protected function redirectWithError(
        string $route,
        string $message,
        array $parameters = []
    ): RedirectResponse {
        return redirect()
            ->route($route, $parameters)
            ->with('error', $message);
    }

    /**
     * Go back with error message
     *
     * @param string $message Error message
     * @return RedirectResponse
     */
    protected function backWithError(string $message): RedirectResponse
    {
        return back()->with('error', $message);
    }

    /**
     * Go back with success message
     *
     * @param string $message Success message
     * @return RedirectResponse
     */
    protected function backWithSuccess(string $message): RedirectResponse
    {
        return back()->with('message', $message);
    }

    /**
     * Redirect with info message
     *
     * @param string $route Route name
     * @param string $message Info message
     * @param array<string, mixed> $parameters Route parameters
     * @return RedirectResponse
     */
    protected function redirectWithInfo(
        string $route,
        string $message,
        array $parameters = []
    ): RedirectResponse {
        return redirect()
            ->route($route, $parameters)
            ->with('info', $message);
    }

    /**
     * Redirect with warning message
     *
     * @param string $route Route name
     * @param string $message Warning message
     * @param array<string, mixed> $parameters Route parameters
     * @return RedirectResponse
     */
    protected function redirectWithWarning(
        string $route,
        string $message,
        array $parameters = []
    ): RedirectResponse {
        return redirect()
            ->route($route, $parameters)
            ->with('warning', $message);
    }

    /**
     * Go back with info message
     *
     * @param string $message Info message
     * @return RedirectResponse
     */
    protected function backWithInfo(string $message): RedirectResponse
    {
        return back()->with('info', $message);
    }

    /**
     * Go back with warning message
     *
     * @param string $message Warning message
     * @return RedirectResponse
     */
    protected function backWithWarning(string $message): RedirectResponse
    {
        return back()->with('warning', $message);
    }
}
