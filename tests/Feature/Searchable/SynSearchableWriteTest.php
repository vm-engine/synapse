<?php

declare(strict_types=1);

use VmEngine\Synapse\Facades\SynSearchable;
use VmEngine\Synapse\Models\Searchable;

it('is disabled and silently no-ops before setup', function () {
    expect(synsearchable()->isEnabled())->toBeFalse();

    SynSearchable::put('content:example.item', '1', 'Acme', url: '/acme');
    SynSearchable::forget('content:example.item', '1');

    expect(SynSearchable::forgetSource('content:example'))->toBe(0)
        ->and(SynSearchable::search('acme'))->toBeEmpty();
});

it('puts a plain-string entry as one locale-less row and upserts it', function () {
    setUpSearchable();

    SynSearchable::put('content:example.item', '1', 'Acme', 'Old', url: '/acme');
    SynSearchable::put('content:example.item', '1', 'Acme Corp', 'New', route: 'example.edit', routeParams: ['id' => 1], icon: 'ph ph-box', acl: 'example.manage', keywords: 'AC-1');

    $rows = Searchable::all();
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->locale)->toBeNull()
        ->and($rows[0]->title)->toBe('Acme Corp')
        ->and($rows[0]->description)->toBe('New')
        ->and($rows[0]->route_params)->toBe(['id' => 1])
        ->and($rows[0]->acl)->toBe('example.manage')
        ->and($rows[0]->keywords)->toBe('AC-1');
});

it('puts a per-locale entry as one row per locale and switches shapes cleanly', function () {
    setUpSearchable();

    SynSearchable::put('content:example.item', '1', ['en' => 'Invoice', 'id' => 'Faktur'], 'Shared', url: '/x');

    expect(Searchable::orderBy('locale')->pluck('title', 'locale')->all())->toBe(['en' => 'Invoice', 'id' => 'Faktur'])
        ->and(Searchable::pluck('description')->unique()->values()->all())->toBe(['Shared']);

    SynSearchable::put('content:example.item', '1', 'Invoice', url: '/x');

    expect(Searchable::count())->toBe(1)
        ->and(Searchable::first()->locale)->toBeNull();
});

it('forgets one key across all locales', function () {
    setUpSearchable();
    SynSearchable::put('content:example.item', '1', ['en' => 'A', 'id' => 'B'], url: '/x');
    SynSearchable::put('content:example.item', '2', 'C', url: '/y');

    SynSearchable::forget('content:example.item', '1');

    expect(Searchable::pluck('key')->all())->toBe(['2']);
});

it('forgets by literal source prefix and returns the count', function () {
    setUpSearchable();
    SynSearchable::put('content:example.item', '1', 'A', url: '/a');
    SynSearchable::put('content:example.category', '1', 'B', url: '/b');
    SynSearchable::put('content:other.item', '1', 'C', url: '/c');
    Searchable::create(['source' => 'nav', 'key' => 'home', 'title' => 'Home', 'route' => 'home']);

    expect(SynSearchable::forgetSource('content:example'))->toBe(2)
        ->and(Searchable::pluck('source')->sort()->values()->all())->toBe(['content:other.item', 'nav']);
});

it('refuses a forgetSource prefix outside content:', function () {
    setUpSearchable();
    Searchable::create(['source' => 'nav', 'key' => 'home', 'title' => 'Home', 'route' => 'home']);

    expect(SynSearchable::forgetSource(''))->toBe(0)
        ->and(SynSearchable::forgetSource('n'))->toBe(0)
        ->and(Searchable::count())->toBe(1);
});

it('rejects reserved sources and entries without a link', function (Closure $call) {
    setUpSearchable();

    expect($call)->toThrow(InvalidArgumentException::class);
})->with([
    'nav source' => [fn () => SynSearchable::put('nav', '1', 'A', url: '/a')],
    'module source' => [fn () => SynSearchable::put('module:example', '1', 'A', url: '/a')],
    'forget nav' => [fn () => SynSearchable::forget('nav', '1')],
    'no route or url' => [fn () => SynSearchable::put('content:x', '1', 'A')],
]);
