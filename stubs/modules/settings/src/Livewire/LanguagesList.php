<?php

declare(strict_types=1);

namespace SynApps\Modules\Settings\Livewire;

use Livewire\Component;
use VmEngine\Synapse\Models\Language;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

/**
 * @property \Illuminate\Support\Collection $languages
 */
class LanguagesList extends Component
{
    public string $search = '';

    public string $currentLanguage = 'en';

    public bool $multiLanguageEnabled = false;

    public array $enabledLanguages = [];

    public int $perPage = 20;

    protected bool $hasMoreRecords = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'currentLanguage' => ['except' => 'en'],
    ];

    public function mount(): void
    {
        $this->multiLanguageEnabled = config('synapps.apps.multi_language', false);
        $this->enabledLanguages = config('synapps.apps.enabled_languages', ['en']);

        // Set current language to first enabled language if not set or invalid
        if (! in_array($this->currentLanguage, $this->enabledLanguages)) {
            $this->currentLanguage = $this->enabledLanguages[0] ?? 'en';
        }
    }

    public function setLanguage(string $languageCode): void
    {
        $this->currentLanguage = $languageCode;
        $this->perPage = 20;
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    public function updateLanguage(int $id, string $value): void
    {
        $language = Language::findOrFail($id);
        $language->value = $value;
        $language->save();

        $this->dispatch('notify',
            variant: 'success',
            title: __('settings::page.languages.saved'),
            message: ''
        );
    }

    public function updatedSearch(): void
    {
        $this->perPage = 20;
    }

    public function getLanguagesProperty()
    {
        // Fetch one extra to check if there are more
        $languages = $this->buildQuery()->limit($this->perPage + 1)->get();

        // Store whether we have more for the hasMore property
        $this->hasMoreRecords = $languages->count() > $this->perPage;

        // Return only the requested amount
        return $languages->take($this->perPage);
    }

    public function getHasMoreProperty(): bool
    {
        // This is set during getLanguagesProperty()
        return $this->hasMoreRecords ?? false;
    }

    protected function buildQuery()
    {
        $query = Language::query();

        // Filter by current language
        $query->where('language_code', $this->currentLanguage);

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('label', 'like', '%'.$this->search.'%')
                    ->orWhere('key', 'like', '%'.$this->search.'%')
                    ->orWhere('value', 'like', '%'.$this->search.'%');
            });
        }

        // Sort by key
        $query->orderBy('key', 'asc');

        return $query;
    }

    public function render()
    {
        $breadcrumbs = Breadcrumbs::make(
            label: 'Settings',
            icon: 'fa-solid fa-gear',
        )->add(
            label: 'Languages',
            icon: 'fa-solid fa-language',
        );

        synav()->setActiveMenu('settings.languages');

        return view('settings::livewire.languages-list', [
            'languages' => $this->languages,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
