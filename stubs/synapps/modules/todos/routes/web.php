<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Todos\Livewire\TodoIndex;

Route::group([], function () {
    Route::get('/', TodoIndex::class)->name('index');
});
