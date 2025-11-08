<?php

use Illuminate\Support\Facades\Route;
use SynApps\Modules\Home\Controllers\HomeController;

Route::middleware('guest')->get('/', [HomeController::class, 'index'])->name('guest');
