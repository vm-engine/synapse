<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\SaveButton;

describe('SaveButton Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(SaveButton::class, 'synapse-save-button');
    });

    it('calls the action and shows a busy state while it runs', function () {
        $html = Blade::render('<x-synapse-save-button action="store" target="files" />');

        expect($html)->toContain('wire:click="store"')->toContain('wire:target="store, files"')
            ->toContain('wire:loading.attr="disabled"')->toContain('ph ph-spinner syn-spin')
            ->toContain('Save')->toContain('Saving…');
    });

    it('renders a submit button without wire:click', function () {
        expect(Blade::render('<x-synapse-save-button submit />'))
            ->toContain('type="submit"')->not->toContain('wire:click');
    });

    it('falls back to save for an unsafe action', function () {
        expect(Blade::render('<x-synapse-save-button action="a();b" />'))->toContain('wire:click="save"');
    });
});
