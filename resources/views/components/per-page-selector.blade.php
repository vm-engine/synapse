<div class="flex gap-2 p-3">
    <div class="flex-none p-1">
        <select class="shadow-theme-sm rounded-sm p-1" wire:model.live="perPage">
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
