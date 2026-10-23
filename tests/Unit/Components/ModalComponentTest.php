<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Modal;

describe('Modal Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Modal::class, 'synapse-modal');
    });

    it('renders only the slot when no title or footer is given', function () {
        $html = Blade::render('<x-synapse-modal name="plain"><p>Body</p></x-synapse-modal>');

        expect($html)->toContain('<p>Body</p>')
            ->not->toContain('syn-modal-header')
            ->not->toContain('syn-modal-footer');
    });

    it('renders a header with a labelled close button when a title is given', function () {
        $html = Blade::render('<x-synapse-modal name="edit" title="Edit group">Body</x-synapse-modal>');

        expect($html)->toContain('syn-modal-header')
            ->toContain('Edit group')
            ->toContain("\$dispatch('close-modal-edit')")
            ->toContain('sr-only')
            ->toContain('aria-labelledby="modal-edit-title"')
            ->toContain('id="modal-edit-title"');
    });

    it('renders the footer slot in an action bar', function () {
        $html = Blade::render('<x-synapse-modal name="edit" title="T">Body<x-slot:footer><button>Save</button></x-slot:footer></x-synapse-modal>');

        expect($html)->toContain('syn-modal-footer')->toContain('<button>Save</button>');
    });
});
