<div class="syn-per-page-selector">
    <div class="flex-none p-1">
        <select
            class="syn-per-page-select"
            wire:model.live="perPage"
        >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>
    <div class="pagination-container flex-1 grow">
        {{ $paginator->links() }}
    </div>
</div>
