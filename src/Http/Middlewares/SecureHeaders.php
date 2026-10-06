<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Http\Middlewares;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Bound before $next() so views rendered during the request can
        // read csp_nonce() and stamp it onto their inline <script> tags.
        $nonce = base64_encode(random_bytes(16));
        app()->instance('csp.nonce', $nonce);

        // Livewire's @livewireScripts/@livewireStyles (and @vite()) default
        // their own nonce to Vite::cspNonce(), which generates an
        // independent random value if never told otherwise. Without this,
        // that nonce never matches the one in our CSP header below, so
        // Livewire's inline bootstrap <script>/<style> tags get blocked.
        app(Vite::class)->useCspNonce($nonce);

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
     * All seven directives below (script-src, style-src, img-src, font-src,
     * connect-src, frame-src, media-src) are config-driven.
     *
     * `script-src` has no `unsafe-eval`: `SynapseServiceProvider::overrideLivewireCsp()`
     * forces `livewire.csp_safe` on, which swaps Alpine to its CSP-safe
     * evaluator (no `new Function()`/`eval()`), so it's no longer needed.
     * `style-src 'unsafe-inline'` is kept for now (deferred hardening).
     */
    protected function buildContentSecurityPolicy(string $nonce): string
    {
        $extra = config('synapps.apps.csp', []);

        $scriptSrc = array_merge(["'self'", "'nonce-{$nonce}'"], $extra['script_src'] ?? []);
        $styleSrc = array_merge(["'self'", "'unsafe-inline'"], $extra['style_src'] ?? []);
        $imgSrc = array_merge(["'self'", 'data:'], $extra['img_src'] ?? []);
        $fontSrc = array_merge(["'self'", 'data:'], $extra['font_src'] ?? []);
        $connectSrc = array_merge(["'self'"], $extra['connect_src'] ?? []);
        $frameSrc = array_merge(["'self'"], $extra['frame_src'] ?? []);
        $mediaSrc = array_merge(["'self'"], $extra['media_src'] ?? []);

        $directives = [
            "default-src 'self' data:",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            'img-src '.implode(' ', $imgSrc),
            'font-src '.implode(' ', $fontSrc),
            'connect-src '.implode(' ', $connectSrc),
            'frame-src '.implode(' ', $frameSrc),
            'media-src '.implode(' ', $mediaSrc),
            "frame-ancestors 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives).';';
    }
}
