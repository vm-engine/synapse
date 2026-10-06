<?php

use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use VmEngine\Synapse\Models\Searchable;

new class extends Component
{
    public string $term = '';

    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = auth()->check() && synsearchable()->isEnabled();
    }

    /**
     * @return Collection<int, Searchable>
     */
    public function getResultsProperty(): Collection
    {
        if (! $this->enabled || mb_strlen(trim($this->term)) < 2) {
            return new Collection;
        }

        return synsearchable()->search($this->term);
    }
};
