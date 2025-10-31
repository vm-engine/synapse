<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Home\Controllers\HomeController;

Route::group([], function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('home');
});
