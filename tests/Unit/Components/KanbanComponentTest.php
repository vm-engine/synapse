<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Kanban;
use VmEngine\Synapse\Components\KanbanCard;
use VmEngine\Synapse\Components\KanbanColumn;

describe('Kanban Components', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Kanban::class, 'synapse-kanban');
        Blade::component(KanbanColumn::class, 'synapse-kanban.column');
        Blade::component(KanbanCard::class, 'synapse-kanban.card');
    });

    it('wires columns and cards to the board sort method via wire:sort groups', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-kanban sort-method="moveItem">
                <x-synapse-kanban.column id="draft" label="Draft" :count="1">
                    <x-synapse-kanban.card id="5">Card five</x-synapse-kanban.card>
                </x-synapse-kanban.column>
            </x-synapse-kanban>
            BLADE);

        expect($html)->toContain('wire:sort="moveItem"')
            ->toContain('wire:sort:group="kanban"')
            ->toContain('wire:sort:group-id="draft"')
            ->toContain('wire:sort:item="5"')
            ->toContain('wire:sort:handle')
            ->toContain('Card five')
            ->toContain('syn-kanban-count')
            ->not->toContain('syn-kanban-empty');
    });

    it('shows the empty hint outside the sortable items for an empty column', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-kanban sort-method="moveItem">
                <x-synapse-kanban.column id="done" label="Done" empty-text="Drop items here" />
            </x-synapse-kanban>
            BLADE);

        expect($html)->toContain('syn-kanban-empty')
            ->toContain('wire:sort:ignore')
            ->toContain('Drop items here');
    });

    it('still shows the empty hint when the slot only holds Livewire block markers', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-kanban sort-method="moveItem">
                <x-synapse-kanban.column id="done" label="Done"><!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]--></x-synapse-kanban.column>
            </x-synapse-kanban>
            BLADE);

        expect($html)->toContain('syn-kanban-empty');
    });

    it('drops an unsafe sort method and group', function () {
        $board = new Kanban(sortMethod: 'move(); alert(1)', group: 'a b');

        expect($board->sortMethod)->toBeNull()
            ->and($board->group)->toBe('kanban');
    });

    it('falls back to gray for unknown column colors', function () {
        expect((new KanbanColumn(id: 'x', label: 'X', color: 'pink'))->color)->toBe('gray')
            ->and((new KanbanColumn(id: 'x', label: 'X', color: 'warning'))->color)->toBe('warning');
    });

    it('does not make a card with an unsafe id sortable', function () {
        expect((new KanbanCard(id: "1' onclick='x"))->id)->toBeNull();
    });
});
