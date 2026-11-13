<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\SectionHeading;

it('renders the section title and optional help', function () {
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
    Blade::component(SectionHeading::class, 'synapse-section-heading');

    expect(Blade::render('<x-synapse-section-heading title="Contact" help="Who we call." />'))
        ->toContain('syn-section-label')->toContain('Contact')->toContain('form-help')->toContain('Who we call.')
        ->and(Blade::render('<x-synapse-section-heading title="X" />'))->not->toContain('form-help');
});
