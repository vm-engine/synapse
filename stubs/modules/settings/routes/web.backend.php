<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Settings\Livewire\ConfigurationList;
use SynApps\Modules\Settings\Livewire\LanguagesList;

Route::group([], function () {
    Route::get('configuration', ConfigurationList::class)
        ->name('configuration');

    Route::get('languages', LanguagesList::class)
        ->name('languages');
});
