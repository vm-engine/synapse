<?php

use Illuminate\Support\Facades\Route;

Route::group([], function () {
    Route::livewire('/', 'todos.todo-index')->name('index');
});
