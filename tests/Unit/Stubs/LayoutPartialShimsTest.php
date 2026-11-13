<?php

declare(strict_types=1);

$stub = fn (string $path): string => (string) file_get_contents(__DIR__.'/../../../stubs/'.$path);

it('keeps the old layout partials as shims over the components', function () use ($stub) {
    expect($stub('synapps/resources/views/components/layouts/partials/breadcrumbs.blade.php'))
        ->toContain('<x-synapse-breadcrumbs :breadcrumbs="$breadcrumbs ?? null" />')
        ->and($stub('synapps/resources/views/components/layouts/partials/loader.blade.php'))
        ->toContain('<x-synapse-loader />')->not->toContain('<script');
});

it('renders the loader once in every backend stub layout', function (string $layout) use ($stub) {
    expect(substr_count($stub($layout), '<x-synapse-loader />'))->toBe(1);
})->with([
    'themes/default/layouts/app.blade.php',
    'themes/application/layouts/app.blade.php',
    'synapps/resources/views/components/layouts/app.blade.php',
]);
