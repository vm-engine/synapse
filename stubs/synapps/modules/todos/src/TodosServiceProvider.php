<?php

declare(strict_types=1);

namespace SynApps\Modules\Todos;

use Illuminate\Support\ServiceProvider;
use VmEngine\Synapse\Traits\AutoRegistersComponents;

class TodosServiceProvider extends ServiceProvider
{
    use AutoRegistersComponents;

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerComponents();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'todos');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'todos');
    }
}
