<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\EmptyState;

describe('EmptyState Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(EmptyState::class, 'synapse-empty-state');
    });

    it('renders icon, title and text', function () {
        $html = Blade::render('<x-synapse-empty-state icon="ph ph-users" title="Belum ada anggota" text="Tambah anggota pertama." />');

        expect($html)->toContain('ph ph-users')->toContain('Belum ada anggota')->toContain('Tambah anggota pertama.')
            ->toContain('aria-hidden="true"');
    });

    it('renders the slot as the action', function () {
        expect(Blade::render('<x-synapse-empty-state title="X"><a class="btn">New</a></x-synapse-empty-state>'))
            ->toContain('<a class="btn">New</a>');
    });

    it('defaults the icon', function () {
        expect((new EmptyState(title: 'X'))->icon)->toBe('ph ph-tray');
    });
});
