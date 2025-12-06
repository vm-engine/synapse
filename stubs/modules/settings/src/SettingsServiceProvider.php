<?php

declare(strict_types=1);

namespace SynApps\Modules\Settings;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SynApps\Modules\Settings\Livewire\ConfigurationList;
use SynApps\Modules\Settings\Livewire\LanguagesList;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Livewire components
        Livewire::component('settings::configuration-list', ConfigurationList::class);
        Livewire::component('settings::languages-list', LanguagesList::class);
    }
}
