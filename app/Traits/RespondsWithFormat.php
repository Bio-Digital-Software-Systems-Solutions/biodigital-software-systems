<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Trait for responding based on request type (JSON vs HTML)
 *
 * Automatically detects if the request expects JSON (API) or HTML (web)
 * and returns the appropriate response format.
 *
 * @package App\Traits
 */
trait RespondsWithFormat
{
    /**
     * Return appropriate success response based on request type
     *
     * @param Request $request HTTP request
     * @param string $route Redirect route name (for HTML responses)
     * @param string $message Success message
     * @param array<string, mixed> $routeParams Route parameters
     * @param array<string, mixed> $jsonData Additional JSON data
     */
    protected function respondSuccess(
        Request $request,
        string $route,
        string $message,
        array $routeParams = [],
        array $jsonData = []
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                ...$jsonData,
            ], 200);
        }

        return redirect()
            ->route($route, $routeParams)
            ->with('message', $message);
    }

    /**
     * Return error response based on request type
     *
     * @param Request $request HTTP request
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array<string, mixed> $errors Validation errors (optional)
     */
    protected function respondError(
        Request $request,
        string $message,
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            $response = [
                'success' => false,
                'message' => $message,
            ];

            if ($errors !== []) {
                $response['errors'] = $errors;
            }

            return response()->json($response, $statusCode);
        }

        return back()
            ->with('error', $message)
            ->withErrors($errors);
    }

    /**
     * Return created resource response
     *
     * @param Request $request HTTP request
     * @param string $route Redirect route name (for HTML responses)
     * @param string $message Success message
     * @param mixed $resource Created resource data (for JSON responses)
     * @param array<string, mixed> $routeParams Route parameters
     */
    protected function respondCreated(
        Request $request,
        string $route,
        string $message,
        mixed $resource = null,
        array $routeParams = []
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $resource,
            ], 201);
        }

        return redirect()
            ->route($route, $routeParams)
            ->with('message', $message);
    }

    /**
     * Return no content response (for delete operations)
     *
     * @param Request $request HTTP request
     * @param string $route Redirect route name (for HTML responses)
     * @param string $message Success message
     * @param array<string, mixed> $routeParams Route parameters
     */
    protected function respondDeleted(
        Request $request,
        string $route,
        string $message,
        array $routeParams = []
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ], 200);
        }

        return redirect()
            ->route($route, $routeParams)
            ->with('message', $message);
    }

    /**
     * Return validation error response
     *
     * @param Request $request HTTP request
     * @param array<string, array<int, string>> $errors Validation errors
     * @param string $message Error message
     */
    protected function respondValidationError(
        Request $request,
        array $errors,
        string $message = 'The given data was invalid.'
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()
            ->withErrors($errors)
            ->with('error', $message)
            ->withInput();
    }

    /**
     * Return unauthorized response
     *
     * @param Request $request HTTP request
     * @param string $message Error message
     */
    protected function respondUnauthorized(
        Request $request,
        string $message = 'Unauthorized.'
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return back()->with('error', $message);
    }

    /**
     * Return not found response
     *
     * @param Request $request HTTP request
     * @param string $message Error message
     */
    protected function respondNotFound(
        Request $request,
        string $message = 'Resource not found.'
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 404);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', $message);
    }
}
