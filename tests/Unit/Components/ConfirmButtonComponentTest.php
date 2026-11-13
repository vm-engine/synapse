<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\ConfirmButton;

describe('ConfirmButton Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(ConfirmButton::class, 'synapse-confirm-button');
    });

    it('carries the token in a data attribute and dispatches confirm-dialog from the dataset', function () {
        $html = Blade::render('<x-synapse-confirm-button method="delete" token="tok123" label="Delete" title="Delete?" message="Sure?" component="abc" />');

        expect($html)->toContain('data-param="tok123"')->toContain('data-method="delete"')
            ->toContain('data-confirm="Delete"')->toContain('data-component="abc"')
            ->toContain('confirm-dialog')
            ->toContain('btn-icon danger has-tooltip')->toContain('<span class="tooltip">Delete</span>');
    });

    it('renders a labelled button with a custom confirm text', function () {
        $html = Blade::render('<x-synapse-confirm-button method="void" param="7" label="Void" title="t" message="m" icon="ph ph-prohibit" color="warning" confirm-text="Yes" labelled />');

        expect($html)->toContain('class="btn warning"')->toContain('Void')->toContain('data-param="7"')
            ->toContain('data-confirm="Yes"')->not->toContain('tooltip');
    });

    it('accepts an integer param', function () {
        expect((new ConfirmButton(method: 'void', label: 'L', title: 't', message: 'm', param: 7))->param)->toBe(7);
    });

    it('sends no parameter when given neither token nor param', function () {
        $html = Blade::render('<x-synapse-confirm-button method="bulkDelete" label="Delete" title="t" message="m" labelled />');

        expect($html)->toContain('data-param=""')->toContain("wireParams: \$el.dataset.param === '' ? [] : [\$el.dataset.param]");
    });

    it('drops a method name that is not a bare identifier', function () {
        $html = Blade::render('<x-synapse-confirm-button method="x();alert(1)" token="t" label="L" title="t" message="m" />');

        expect($html)->toContain('data-method=""');
    });
});
