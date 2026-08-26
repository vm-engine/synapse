<?php

declare(strict_types=1);

use VmEngine\Synapse\Services\Security\SsrfGuard;

describe('SsrfGuard::isSafeUrl', function () {
    it('allows an empty string (not yet configured)', function () {
        expect(SsrfGuard::isSafeUrl(''))->toBeTrue();
    });

    it('rejects an unparseable value', function () {
        expect(SsrfGuard::isSafeUrl('not a url'))->toBeFalse();
    });

    it('rejects non-http(s) schemes', function () {
        expect(SsrfGuard::isSafeUrl('ftp://example.com'))->toBeFalse();
        expect(SsrfGuard::isSafeUrl('file:///etc/passwd'))->toBeFalse();
        expect(SsrfGuard::isSafeUrl('gopher://127.0.0.1:6379/'))->toBeFalse();
    });

    it('rejects loopback addresses', function () {
        expect(SsrfGuard::isSafeUrl('http://127.0.0.1/'))->toBeFalse();
        expect(SsrfGuard::isSafeUrl('http://127.0.0.1:8899/'))->toBeFalse();
        expect(SsrfGuard::isSafeUrl('http://[::1]/'))->toBeFalse();
    });

    it('allows RFC1918 private ranges (e.g. VPN/leased-line bank endpoints)', function () {
        expect(SsrfGuard::isSafeUrl('http://10.0.119.14:801/'))->toBeTrue();
        expect(SsrfGuard::isSafeUrl('http://172.16.0.5/'))->toBeTrue();
        expect(SsrfGuard::isSafeUrl('http://192.168.1.1/'))->toBeTrue();
    });

    it('rejects link-local addresses including cloud metadata', function () {
        expect(SsrfGuard::isSafeUrl('http://169.254.169.254/latest/meta-data/'))->toBeFalse();
    });

    it('rejects a hostname that resolves to a private address', function () {
        // "localhost" reliably resolves to a loopback address in every environment,
        // no network access or mocking required.
        expect(SsrfGuard::isSafeUrl('http://localhost/'))->toBeFalse();
    });

    it('allows a public IP literal, no DNS resolution needed', function () {
        expect(SsrfGuard::isSafeUrl('http://8.8.8.8/'))->toBeTrue();
        expect(SsrfGuard::isSafeUrl('https://8.8.8.8/'))->toBeTrue();
    });
});
