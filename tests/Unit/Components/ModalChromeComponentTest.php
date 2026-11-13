<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\ModalFooter;
use VmEngine\Synapse\Components\ModalHeader;
use VmEngine\Synapse\Components\SaveButton;

describe('Modal header/footer components', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(ModalHeader::class, 'synapse-modal-header');
        Blade::component(ModalFooter::class, 'synapse-modal-footer');
        Blade::component(SaveButton::class, 'synapse-save-button');
    });

    it('renders a heading with the id the modal is labelled by, and a close button', function () {
        $html = Blade::render('<x-synapse-modal-header modal="tax-rate-form" title="Edit" />');

        expect($html)->toContain('syn-modal-header')->toContain('id="modal-tax-rate-form-title"')
            ->toContain('close-modal-tax-rate-form')->toContain('Close');
    });

    it('renders cancel + save, and a slot instead of save', function () {
        expect(Blade::render('<x-synapse-modal-footer modal="m" action="store" />'))
            ->toContain('syn-modal-footer')->toContain('close-modal-m')->toContain('wire:click="store"')
            ->and(Blade::render('<x-synapse-modal-footer modal="m"><button>Go</button></x-synapse-modal-footer>'))
            ->toContain('<button>Go</button>')->not->toContain('wire:click="save"');
    });

    it('drops an unsafe modal name', function () {
        expect(Blade::render('<x-synapse-modal-header modal="a);x(" title="t" />'))->not->toContain('x(');
    });
});
