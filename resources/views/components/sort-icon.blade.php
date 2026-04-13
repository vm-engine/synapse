<a href="#" wire:click.prevent="sortBy('{{ $field }}')">
    <span class="fa fa-solid {{ $sortField !== $field ? 'fa-sort' : ($sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') }}"></span>
</a>
