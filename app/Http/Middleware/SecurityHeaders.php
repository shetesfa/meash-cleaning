<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and apply strict security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers for defense-in-depth
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // X-XSS-Protection is removed on purpose: modern browsers ignore
        // it, and its old auditor could turn a reflected value into an
        // XSS hole instead of blocking it. CSP is the protection now.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        // HSTS only on real HTTPS traffic in production: sending it over
        // plain HTTP or on a dev machine would be wrong (browsers ignore
        // it on HTTP, and a local/staging host must never be pinned).
        if ($request->secure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Telegram MiniApp routes need to be embeddable in Telegram Web
        if ($request->is('telegram*') || $request->is('api/telegram*')) {
            $response->headers->set('Content-Security-Policy', "default-src 'self' https: data:; script-src 'self' 'unsafe-inline' https://telegram.org https://cdn.tailwindcss.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'self' https://web.telegram.org https://*.telegram.org;");
        } else {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Content-Security-Policy', "default-src 'self' https: data:; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data: https:; connect-src 'self' https:; frame-ancestors 'self';");
        }

        return $response;
    }
}
