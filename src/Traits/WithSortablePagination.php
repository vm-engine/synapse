<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

/**
 * Provides sortable column toggling, sort-field whitelist validation,
 * per-page selection, and automatic page reset on filter changes
 * for Livewire list components.
 *
 * Requires Livewire\WithPagination to be used alongside this trait.
 *
 * Override `allowedSortFields()` to declare which columns are sortable.
 *
 * @phpstan-ignore trait.unused
 */
trait WithSortablePagination
{
    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    public int $perPage = 15;

    /**
     * Columns the user is allowed to sort by.
     * Override in component to declare sortable columns.
     *
     * @return array<int, string>
     */
    protected function allowedSortFields(): array
    {
        return ['id'];
    }

    /**
     * Toggle sort direction if clicking the same column,
     * otherwise switch to the new column with ascending order.
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Return a validated sort field, falling back to 'id'
     * if the current value is not in the allowed list.
     */
    protected function validatedSortField(): string
    {
        $allowed = $this->allowedSortFields();

        return in_array($this->sortField, $allowed, true) ? $this->sortField : 'id';
    }

    /**
     * Return a validated sort direction ('asc' or 'desc').
     */
    protected function validatedSortDirection(): string
    {
        return in_array($this->sortDirection, ['asc', 'desc'], true)
            ? $this->sortDirection
            : 'desc';
    }
}
