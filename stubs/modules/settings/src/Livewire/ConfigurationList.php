<?php

declare(strict_types=1);

namespace SynApps\Modules\Settings\Livewire;

use Livewire\Component;
use VmEngine\Synapse\Models\Config;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

/**
 * @property \Illuminate\Support\Collection $configs
 */
class ConfigurationList extends Component
{
    public string $search = '';

    public int $perPage = 20;

    protected bool $hasMoreRecords = false;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Initialize
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function updateConfig(int $id, string $value): void
    {
        $config = Config::findOrFail($id);
        $config->value = $value;
        $config->save();

        $this->dispatch('notify',
            variant: 'success',
            title: __('settings::page.configuration.saved'),
            message: ''
        );
    }

    public function updatedSearch(): void
    {
        $this->perPage = 20;
    }

    public function getConfigsProperty()
    {
        // Fetch one extra to check if there are more
        $configs = $this->buildQuery()->limit($this->perPage + 1)->get();

        // Store whether we have more for the hasMore property
        $this->hasMoreRecords = $configs->count() > $this->perPage;

        // Return only the requested amount
        return $configs->take($this->perPage);
    }

    public function getHasMoreProperty(): bool
    {
        // This is set during getConfigsProperty()
        return $this->hasMoreRecords ?? false;
    }

    protected function buildQuery()
    {
        $query = Config::query();

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('label', 'like', '%'.$this->search.'%')
                    ->orWhere('key', 'like', '%'.$this->search.'%')
                    ->orWhere('group', 'like', '%'.$this->search.'%')
                    ->orWhere('value', 'like', '%'.$this->search.'%');
            });
        }

        // Sort by group and key
        $query->orderBy('group', 'asc')
            ->orderBy('key', 'asc');

        return $query;
    }

    public function render()
    {
        $breadcrumbs = Breadcrumbs::make(
            label: 'Settings',
            icon: 'fa-solid fa-gear',
        )->add(
            label: 'Configuration',
            icon: 'fa-solid fa-sliders',
        );

        synav()->setActiveMenu('settings.configuration');

        return view('settings::livewire.configuration-list', [
            'configs' => $this->configs,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
