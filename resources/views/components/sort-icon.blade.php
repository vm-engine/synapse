<a
    href="#"
    wire:click.prevent="sortBy('{{ $field }}')"
>
    <span
        class="ph {{ $sortField !== $field ? 'ph-arrows-down-up' : ($sortDirection === 'asc' ? 'ph-caret-up' : 'ph-caret-down') }}"
    ></span>
</a>
