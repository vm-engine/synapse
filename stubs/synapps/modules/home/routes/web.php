<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Home\Controllers\HomeController;

Route::middleware('guest')->get('/', [HomeController::class, 'index'])->name('guest');
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('home');
});
