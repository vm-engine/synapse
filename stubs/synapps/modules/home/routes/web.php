<?php

use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::livewire('/', 'home::landing')->name('landing');
});
