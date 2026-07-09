<?php

namespace VmEngine\Synapse\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;

class SecureHeaders
{
    public function handle(Request $request, Closure $next)
    {
        // Bound before $next() so views rendered during the request can
        // read csp_nonce() and stamp it onto their inline <script> tags.
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp.nonce', $nonce);

        $response = $next($request);

        if (app()->environment('local')) {
            return $response;
        }

        $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Content-Security-Policy', $this->buildContentSecurityPolicy($nonce));

        // Additional headers
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Expect-CT', 'max-age=86400, enforce');
        $response->headers->set('Feature-Policy', "geolocation 'none'; microphone 'none'");

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     *
     * Extra allowed hosts per directive come from the app-level
     * `csp` block in `synapps/config/apps.json` (config('synapps.apps.csp'))
     * so this shared package doesn't hardcode any one project's CDN hosts.
     *
     * `unsafe-eval` and `style-src 'unsafe-inline'` are kept for now:
     * Alpine.js's default build needs `unsafe-eval` to evaluate directive
     * expressions, and removing it requires swapping to Alpine's dedicated
     * CSP build (`@alpinejs/csp`), which is a separate, higher-risk change.
     */
    protected function buildContentSecurityPolicy(string $nonce): string
    {
        $extra = config('synapps.apps.csp', []);

        $scriptSrc = array_merge(["'self'", "'unsafe-eval'", "'nonce-{$nonce}'"], $extra['script_src'] ?? []);
        $styleSrc = array_merge(["'self'", "'unsafe-inline'"], $extra['style_src'] ?? []);
        $imgSrc = array_merge(["'self'", 'data:'], $extra['img_src'] ?? []);
        $fontSrc = array_merge(["'self'", 'data:'], $extra['font_src'] ?? []);
        $connectSrc = array_merge(["'self'"], $extra['connect_src'] ?? []);

        $directives = [
            "default-src 'self' data:",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            'img-src '.implode(' ', $imgSrc),
            'font-src '.implode(' ', $fontSrc),
            'connect-src '.implode(' ', $connectSrc),
            "frame-src 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives).';';
    }
}
