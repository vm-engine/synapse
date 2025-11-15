<?php

declare(strict_types=1);

namespace SynApps\Modules\Todos;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SynApps\Modules\Todos\Livewire\TodoForm;
use SynApps\Modules\Todos\Livewire\TodoIndex;
use SynApps\Modules\Todos\Livewire\TodoList;

class TodosServiceProvider extends ServiceProvider
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
        Livewire::component('todos::todo-index', TodoIndex::class);
        Livewire::component('todos::todo-form', TodoForm::class);
        Livewire::component('todos::todo-list', TodoList::class);

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'todos');

        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'todos');
    }
}
