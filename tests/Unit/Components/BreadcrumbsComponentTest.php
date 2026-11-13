<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Breadcrumbs as BreadcrumbsComponent;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

describe('Breadcrumbs Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(BreadcrumbsComponent::class, 'synapse-breadcrumbs');
    });

    it('renders each crumb as a wire:navigate link with a separator between them', function () {
        $trail = Breadcrumbs::make('Clients', '/clients')->add('Acme', '/clients/1');

        $html = Blade::render('<x-synapse-breadcrumbs :breadcrumbs="$trail" />', ['trail' => $trail]);

        expect($html)->toContain('href="/clients"')->toContain('href="/clients/1"')
            ->toContain('Acme')->toContain('wire:navigate')
            ->and(substr_count($html, 'M6.0765 12.667'))->toBe(2); // home→Clients, Clients→Acme; none after the last
    });

    it('renders only the home link without a trail', function () {
        $html = Blade::render('<x-synapse-breadcrumbs />');

        expect($html)->toContain('<nav')->toContain('aria-label=')->not->toContain('M6.0765 12.667');
    });

    it('marks the last crumb as the current page', function () {
        $trail = Breadcrumbs::make('Clients', '/clients');

        expect(Blade::render('<x-synapse-breadcrumbs :breadcrumbs="$trail" />', ['trail' => $trail]))
            ->toContain('aria-current="page"');
    });
});
