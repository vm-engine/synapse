<?php

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\PerPageSelector;

describe('PerPageSelector Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(PerPageSelector::class, 'synapse-per-page-selector');
    });

    it('names the page-size select, which has no visible label', function () {
        $paginator = new LengthAwarePaginator([], 0, 15);

        $html = Blade::render('<x-synapse-per-page-selector :paginator="$p" />', ['p' => $paginator]);

        expect($html)->toMatch('/<select[^>]*aria-label="Per page"/s');
    });

    it('lets the host override the accessible name', function () {
        $paginator = new LengthAwarePaginator([], 0, 15);

        $html = Blade::render('<x-synapse-per-page-selector :paginator="$p" label="Baris per halaman" />', ['p' => $paginator]);

        expect($html)->toContain('aria-label="Baris per halaman"')
            ->not->toContain('aria-label="Per page"');
    });
});
