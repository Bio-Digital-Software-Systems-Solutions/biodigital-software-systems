<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate nonce for inline scripts and styles
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        // Content Security Policy - Adapted for Vite in development, strict in production
        $isDev = app()->environment('local');

        if ($isDev) {
            // Development: Allow Vite HMR, inline styles, and external resources
            // Note: Using specific common Vite ports since wildcard ports aren't fully supported in CSP
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; ".
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://localhost:5174 http://localhost:5175 http://localhost:5176 http://localhost:5177 http://127.0.0.1:5173 http://127.0.0.1:5174 http://127.0.0.1:5175 http://127.0.0.1:5176 http://127.0.0.1:5177 https://www.youtube.com https://www.youtube-nocookie.com; ".
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; ".
                "img-src 'self' data: https: http: blob:; ".
                "font-src 'self' data: https://fonts.bunny.net; ".
                "connect-src 'self' ws://localhost:5173 ws://localhost:5174 ws://localhost:5175 ws://localhost:5176 ws://localhost:5177 ws://127.0.0.1:5173 ws://127.0.0.1:5174 ws://127.0.0.1:5175 ws://127.0.0.1:5176 ws://127.0.0.1:5177 http://localhost:5173 http://localhost:5174 http://localhost:5175 http://localhost:5176 http://localhost:5177 http://127.0.0.1:5173 http://127.0.0.1:5174 http://127.0.0.1:5175 http://127.0.0.1:5176 http://127.0.0.1:5177; ".
                "media-src 'self' blob: https://www.youtube.com https://www.youtube-nocookie.com; ".
                "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com; ".
                "frame-ancestors 'none'; ".
                "base-uri 'self'; ".
                "form-action 'self';"
            );
        } else {
            // Production: CSP with 'unsafe-inline' for Ziggy routes
            // Note: 'unsafe-inline' is needed for @routes directive from Ziggy
            // Vite-generated inline styles also need 'unsafe-inline'
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; ".
                "script-src 'self' 'unsafe-inline' https://www.youtube.com https://www.youtube-nocookie.com; ".
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; ". // Allow external fonts
                "img-src 'self' data: https: blob:; ".
                "font-src 'self' data: https://fonts.bunny.net; ". // Allow external fonts
                "connect-src 'self'; ".
                "media-src 'self' blob: https://www.youtube.com https://www.youtube-nocookie.com; ".
                "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com; ".
                "frame-ancestors 'none'; ".
                "base-uri 'self'; ".
                "form-action 'self'; ".
                'upgrade-insecure-requests;'
            );
        }

        // Empêcher le site d'être intégré dans une iframe
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêcher le MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Activer XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Référer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
        );

        // HSTS (HTTP Strict Transport Security) - Seulement en production avec HTTPS
        if (app()->environment('production') && $request->secure()) {
            $response->headers->set('Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
