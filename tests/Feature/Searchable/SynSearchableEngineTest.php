<?php

declare(strict_types=1);

use Laravel\Scout\Searchable as ScoutTrait;
use VmEngine\Synapse\Facades\SynSearchable;
use VmEngine\Synapse\Models\Searchable;

it('falls back to eloquent when scout is configured but not installed', function () {
    config(['synapps.apps.searchable.engine' => 'scout']);
    setUpSearchable();

    SynSearchable::put('content:t', '1', 'Users', url: '/u');

    expect(Searchable::count())->toBe(1)
        ->and(SynSearchable::search('user')->pluck('key')->all())->toBe(['1']);
})->skip(fn () => trait_exists(ScoutTrait::class), 'laravel/scout is installed');
