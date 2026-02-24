<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

use function Pest\Laravel\get;

test('can access todos index page', function () {
    get('/todos')
        ->assertStatus(200)
        ->assertSee(__('todos::todos.page_title'));
});

test('can render todo index component', function () {
    Livewire::test('todos::todo-index')
        ->assertStatus(200);
});

test('todos index page shows form and list components', function () {
    get('/todos')
        ->assertStatus(200)
        ->assertSee(__('todos::todos.form.title'))
        ->assertSee(__('todos::todos.filters.search'));
});

test('todos index page shows page title', function () {
    get('/todos')
        ->assertSee(__('todos::todos.page_title'))
        ->assertSee(__('todos::todos.page_description'));
});

test('route name is correct', function () {
    expect(route('todos.index'))->toContain('/todos');
});
