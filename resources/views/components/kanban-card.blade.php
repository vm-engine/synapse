{{--
    Kanban Card — child of a synapse kanban column. Drag by the handle;
    wrap buttons/links inside the card in wire:sort:ignore if they sit on the handle.

    Props:
    - id (string|int): Item identifier passed to the board's sortMethod
--}}

<div
    class="syn-kanban-card"
    @if ($id) wire:sort:item="{{ $id }}" @endif
    {{ $attributes }}
>
    <span
        class="syn-kanban-handle"
        wire:sort:handle
        aria-hidden="true"
    ><i class="ph ph-dots-six-vertical"></i></span>
    <div class="syn-kanban-card-body">{{ $slot }}</div>
</div>
