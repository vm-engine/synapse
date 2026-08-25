<?php

declare(strict_types=1);

test('csp_nonce returns empty string when nothing bound', function () {
    expect(csp_nonce())->toBe('');
});

test('csp_nonce returns the bound nonce value', function () {
    app()->instance('csp.nonce', 'abc123');

    expect(csp_nonce())->toBe('abc123');
});
