<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use VmEngine\Synapse\Models\Searchable;

it('is not created by a plain migrate', function () {
    expect(Schema::hasTable('searchables'))->toBeFalse();
});

it('creates the table from the searchable migration folder', function () {
    setUpSearchable();

    expect(Schema::hasTable('searchables'))->toBeTrue();
});

it('escapes LIKE wildcards', function () {
    expect(Searchable::escapeLike('a%b_c!d'))->toBe('a!%b!_c!!d');
});

it('scopes by literal source prefix', function () {
    setUpSearchable();
    Searchable::create(['source' => 'content:ex_ample.item', 'key' => '1', 'title' => 'A', 'url' => '/a']);
    Searchable::create(['source' => 'content:exXample.item', 'key' => '2', 'title' => 'B', 'url' => '/b']);

    expect(Searchable::query()->sourcePrefix('content:ex_ample')->pluck('key')->all())->toBe(['1']);
});

it('resolves the backend-prefixed route first, then the bare route', function () {
    setUpSearchable();
    Route::get('/backend/users/{id}', fn () => 'ok')->name('backend.users.edit');
    Route::get('/plain', fn () => 'ok')->name('plain.index');
    app('router')->getRoutes()->refreshNameLookups();

    $prefixed = Searchable::create(['source' => 'nav', 'key' => 'a', 'title' => 'A', 'route' => 'users.edit', 'route_params' => ['id' => 5]]);
    $bare = Searchable::create(['source' => 'nav', 'key' => 'b', 'title' => 'B', 'route' => 'plain.index']);

    expect($prefixed->resolveUrl())->toEndWith('/backend/users/5')
        ->and($bare->resolveUrl())->toEndWith('/plain');
});

it('only resolves relative and http(s) urls', function (string $url, ?string $expected) {
    setUpSearchable();

    $row = Searchable::create(['source' => 'content:x', 'key' => '1', 'title' => 'A', 'url' => $url]);

    expect($row->resolveUrl())->toBe($expected);
})->with([
    'relative' => ['/apps/x', '/apps/x'],
    'https' => ['https://example.com/a', 'https://example.com/a'],
    'http uppercase' => ['HTTP://example.com', 'HTTP://example.com'],
    'javascript' => ['javascript:alert(1)', null],
    'javascript padded' => [' JavaScript:alert(1)', null],
    'data' => ['data:text/html,<b>x</b>', null],
    'protocol-relative' => ['//evil.example', null],
]);

it('returns null for a missing route or missing params, and the raw url when no route', function () {
    setUpSearchable();
    Route::get('/backend/users/{id}', fn () => 'ok')->name('backend.users.edit');
    app('router')->getRoutes()->refreshNameLookups();

    $stale = Searchable::create(['source' => 'nav', 'key' => 'a', 'title' => 'A', 'route' => 'gone.index']);
    $noParams = Searchable::create(['source' => 'nav', 'key' => 'b', 'title' => 'B', 'route' => 'users.edit']);
    $url = Searchable::create(['source' => 'content:x', 'key' => 'c', 'title' => 'C', 'url' => '/custom']);

    expect($stale->resolveUrl())->toBeNull()
        ->and($noParams->resolveUrl())->toBeNull()
        ->and($url->resolveUrl())->toBe('/custom');
});
