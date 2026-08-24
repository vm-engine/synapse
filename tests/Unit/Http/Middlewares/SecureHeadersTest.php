<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Vite;
use VmEngine\Synapse\Http\Middlewares\SecureHeaders;

function runSecureHeaders(): Response
{
    $middleware = new SecureHeaders;
    $request = Request::create('/', 'GET');

    return $middleware->handle($request, fn () => new Response('ok'));
}

test('does not set security headers in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    $response = runSecureHeaders();

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
});

test('sets Content-Security-Policy header outside local environment', function () {
    app()->detectEnvironment(fn () => 'testing');

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->not->toBeNull()
        ->and($csp)->toContain("frame-ancestors 'self'")
        ->and($csp)->toContain("form-action 'self'")
        ->and($csp)->not->toContain('https:');
});

test('script-src carries a nonce and no longer needs unsafe-eval', function () {
    app()->detectEnvironment(fn () => 'testing');

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("script-src 'self' 'nonce-")
        ->and($csp)->not->toContain('unsafe-eval')
        ->and($csp)->not->toContain("script-src 'self' https: 'unsafe-inline'");
});

test('style-src still allows unsafe-inline (deferred hardening)', function () {
    app()->detectEnvironment(fn () => 'testing');

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("style-src 'self' 'unsafe-inline'");
});

test('merges extra allowed hosts from synapps.apps.csp config', function () {
    app()->detectEnvironment(fn () => 'testing');
    config()->set('synapps.apps.csp', [
        'script_src' => ['https://cdn.jsdelivr.net'],
        'style_src' => ['https://fonts.googleapis.com'],
        'font_src' => ['https://fonts.gstatic.com'],
    ]);

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain('https://cdn.jsdelivr.net')
        ->and($csp)->toContain('https://fonts.googleapis.com')
        ->and($csp)->toContain('https://fonts.gstatic.com');
});

test('merges extra allowed hosts for frame_src and media_src config', function () {
    app()->detectEnvironment(fn () => 'testing');
    config()->set('synapps.apps.csp', [
        'frame_src' => ['https://www.google.com'],
        'media_src' => ['https://res.cloudinary.com'],
    ]);

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("frame-src 'self' https://www.google.com")
        ->and($csp)->toContain("media-src 'self' https://res.cloudinary.com");
});

test('frame_src and media_src default to self when not configured', function () {
    app()->detectEnvironment(fn () => 'testing');
    config()->set('synapps.apps.csp', []);

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("frame-src 'self'")
        ->and($csp)->toContain("media-src 'self'");
});

test('the nonce bound for the view matches the nonce in the CSP header', function () {
    app()->detectEnvironment(fn () => 'testing');

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');
    $nonce = csp_nonce();

    expect($nonce)->not->toBe('')
        ->and($csp)->toContain("'nonce-{$nonce}'");
});

test('registers the CSP nonce as the Vite nonce so livewire scripts/styles match', function () {
    app()->detectEnvironment(fn () => 'testing');

    $response = runSecureHeaders();
    $csp = $response->headers->get('Content-Security-Policy');

    $viteNonce = Vite::cspNonce();

    expect($viteNonce)->toBe(csp_nonce())
        ->and($csp)->toContain("'nonce-{$viteNonce}'");
});
