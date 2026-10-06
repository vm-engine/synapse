{{--
    Kanban Board Component

    Horizontal-scrolling board. Drag-drop is Livewire 4's native wire:sort with
    groups: dropping a card calls the host's sortMethod($itemId, $position,
    $destinationColumnId) — only the destination column's handler fires.

    Props:
    - sortMethod (string): Host Livewire method name
    - group (string): wire:sort group shared by this board's columns (default: 'kanban')

    Usage:
    <x-synapse-kanban sort-method="moveItem">
        <x-synapse-kanban.column id="draft" label="Draft" color="gray" :count="$drafts->count()">
            @foreach ($drafts as $item)
                <x-synapse-kanban.card :id="$item->id" wire:key="card-{{ $item->id }}">{{ $item->name }}</x-synapse-kanban.card>
            @endforeach
        </x-synapse-kanban.column>
    </x-synapse-kanban>
--}}

<div
    class="syn-kanban"
    {{ $attributes }}
>
    {{ $slot }}
</div>
