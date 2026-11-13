<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Loader;

describe('Loader Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Loader::class, 'synapse-loader');
    });

    it('renders a hidden overlay bound to the registered Alpine component, with no inline script', function () {
        $html = Blade::render('<x-synapse-loader />');

        expect($html)->toContain('x-data="synapseLoader"')->toContain('x-show="loading"')
            ->toContain('role="status"')->toContain('sr-only')
            ->not->toContain('<script');
    });
});
