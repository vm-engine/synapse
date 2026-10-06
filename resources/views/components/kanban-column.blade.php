{{--
    Kanban Column — child of the synapse kanban board. Reads sortMethod/group
    from the board via @aware.

    Props:
    - id (string|int): Column identifier, passed to sortMethod as the destination column
    - label (string): Column title
    - color (string): 'gray', 'brand', 'success', 'warning', 'danger', 'info' (default: 'gray')
    - count (int): Optional item count badge
    - emptyText (string): Hint shown when the column has no cards (default: 'No items')
--}}

@aware(['sortMethod' => null, 'group' => 'kanban'])

<section
    class="syn-kanban-column"
    {{ $attributes }}
>
    <header class="syn-kanban-column-header">
        <span class="syn-kanban-dot syn-kanban-dot-{{ $color }}"></span>
        <h4 class="syn-kanban-column-title">{{ $label }}</h4>
        @if ($count !== null)
            <span class="syn-kanban-count">{{ $count }}</span>
        @endif
    </header>

    <div
        class="syn-kanban-list"
        @if ($sortMethod && $id) wire:sort="{{ $sortMethod }}"
            wire:sort:group="{{ $group }}"
            wire:sort:group-id="{{ $id }}" @endif
    >
        {{ $slot }}

        @unless ($slot->hasActualContent())
            <div
                class="syn-kanban-empty"
                wire:sort:ignore
            >{{ $emptyText }}</div>
            @endunless
        </div>
    </section>
