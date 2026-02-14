<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Settings\Livewire\ConfigurationList;
use SynApps\Modules\Settings\Livewire\LanguagesList;

Route::group([], function () {
    Route::livewire('configuration', ConfigurationList::class)
        ->name('configuration');

    Route::livewire('languages', LanguagesList::class)
        ->name('languages');
});
