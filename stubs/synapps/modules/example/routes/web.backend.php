<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Example\Livewire\CategoryList;
use SynApps\Modules\Example\Livewire\ExampleForm;
use SynApps\Modules\Example\Livewire\ExampleList;

Route::group([], function () {
    Route::get('/', ExampleList::class)->name('index');
    Route::get('/form/{id?}', ExampleForm::class)->name('form');
    Route::get('/category', CategoryList::class)->name('category');
});
