<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Example\Livewire\CategoryList;
use SynApps\Modules\Example\Livewire\ExampleForm;
use SynApps\Modules\Example\Livewire\ExampleList;

Route::group([], function () {
    Route::get('/', ExampleList::class)->name('index')
        ->middleware('can-access:example.manage');
    Route::get('/form/{id?}', ExampleForm::class)->name('form')
        ->middleware('can-access:example.manage.create|example.manage.update');
    Route::get('/category', CategoryList::class)->name('category')
        ->middleware('can-access:example.category');
});
