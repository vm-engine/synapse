<?php

namespace VmEngine\Synapse\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;

class SecureHeaders
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('local')) {
            return $next($request);
        }
        $response = $next($request);

        $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Content-Security-Policy', "default-src 'self' data:; script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; style-src 'self' https: 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https: data:; connect-src 'self' https:; frame-src 'self';");

        // Additional headers
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Expect-CT', 'max-age=86400, enforce');
        $response->headers->set('Feature-Policy', "geolocation 'none'; microphone 'none'");

        return $response;
    }
}
