<?php

declare(strict_types=1);

// Assertions are written against the English source strings; a test that needs another
// locale switches to it itself (and Laravel resets it for the next test).
it('runs the suite in English', function () {
    expect(app()->getLocale())->toBe('en')
        ->and(config('app.fallback_locale'))->toBe('en');
});
