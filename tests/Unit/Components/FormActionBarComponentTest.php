<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\FormActionBar;
use VmEngine\Synapse\Components\SaveButton;

describe('FormActionBar Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(FormActionBar::class, 'synapse-form-action-bar');
        Blade::component(SaveButton::class, 'synapse-save-button');
    });

    it('renders the required hint, a cancel link and the save button', function () {
        $html = Blade::render('<x-synapse-form-action-bar action="store" cancel-url="/back" />');

        expect($html)->toContain('syn-form-action-bar')->toContain('Required field')
            ->toContain('href="/back"')->toContain('Cancel')->toContain('wire:click="store"');
    });

    it('omits the cancel link without a url, renders slot buttons, and hides the hint on request', function () {
        $html = Blade::render('<x-synapse-form-action-bar :hint="false"><button class="btn">Draft</button></x-synapse-form-action-bar>');

        expect($html)->not->toContain('href=')->toContain('Draft')->not->toContain('Required field');
    });
});
