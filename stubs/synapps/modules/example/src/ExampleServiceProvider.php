<?php

namespace SynApps\Modules\Example;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SynApps\Modules\Example\Livewire\CategoryForm;
use SynApps\Modules\Example\Livewire\CategoryList;
use SynApps\Modules\Example\Livewire\Components\ButtonSample;
use SynApps\Modules\Example\Livewire\ExampleForm;
use SynApps\Modules\Example\Livewire\ExampleList;

class ExampleServiceProvider extends ServiceProvider
{
    public function register()
    {
        Livewire::component('example::button-sample', ButtonSample::class);
        Livewire::component('example-list', ExampleList::class);
        Livewire::component('example-form', ExampleForm::class);
        Livewire::component('category-list', CategoryList::class);
        Livewire::component('category-form', CategoryForm::class);
    }

    public function boot() {}
}
