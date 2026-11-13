<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\PasswordGenerator;

function passwordGeneratorHtml(): string
{
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
    Blade::component(PasswordGenerator::class, 'synapse-password-generator');

    return Blade::render('<x-synapse-password-generator model="password" />');
}

// The icon-only buttons carried an English `title` only — no accessible name — and neither
// they nor the placeholder went through __().
it('names its icon buttons for screen readers', function () {
    expect(passwordGeneratorHtml())->toContain('aria-label="Generate password"')
        ->toContain('aria-label="Apply password"')
        ->toContain('aria-label="Copy password"')
        ->toContain('placeholder="Enter or generate password"');
});

it('passes its labels and placeholder through the translator', function () {
    app('translator')->addLines([
        '*.Generate password' => 'T-generate',
        '*.Apply password' => 'T-apply',
        '*.Copy password' => 'T-copy',
        '*.Enter or generate password' => 'T-placeholder',
    ], 'test');
    app()->setLocale('test');

    expect(passwordGeneratorHtml())->toContain('aria-label="T-generate"')
        ->toContain('aria-label="T-apply"')
        ->toContain('aria-label="T-copy"')
        ->toContain('placeholder="T-placeholder"');
});
