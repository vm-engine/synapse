<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use VmEngine\Synapse\Models\Searchable;

beforeEach(function () {
    // SynapseViewServiceProvider doesn't boot in isolated package tests;
    // register just what the palette needs (same as NotificationBellTest).
    Livewire::addNamespace('synsearch', __DIR__.'/../../../resources/views/components/searchable');
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');

    $this->actingAs((new User)->forceFill(['id' => 1]));
});

it('renders nothing before setup', function () {
    Livewire::test('synsearch::palette')->assertDontSeeHtml('synsearch-trigger');
});

it('renders nothing for guests', function () {
    setUpSearchable();
    auth()->logout();

    Livewire::test('synsearch::palette')->assertDontSeeHtml('synsearch-trigger');
});

it('prompts until two characters are typed', function () {
    setUpSearchable();

    Livewire::test('synsearch::palette')
        ->assertSeeHtml('synsearch-trigger')
        ->set('term', 'u')
        ->assertSee('Type at least 2 characters');
});

it('lists matching results with their links', function () {
    setUpSearchable();
    Route::get('/backend/users', fn () => 'ok')->name('backend.users.index');
    app('router')->getRoutes()->refreshNameLookups();
    Searchable::create(['source' => 'nav', 'key' => 'users', 'title' => 'Users', 'description' => 'Admin', 'route' => 'users.index', 'icon' => 'ph ph-users']);

    Livewire::test('synsearch::palette')
        ->set('term', 'us')
        ->assertSee('Users')
        ->assertSee('Admin')
        ->assertSeeHtml('/backend/users');
});

it('shows an empty state when nothing matches', function () {
    setUpSearchable();

    Livewire::test('synsearch::palette')
        ->set('term', 'zzz')
        ->assertSee('No results found');
});
