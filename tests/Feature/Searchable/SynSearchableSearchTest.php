<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use VmEngine\Synapse\Facades\SynSearchable;
use VmEngine\Synapse\Models\Searchable;

function searchableRow(array $attributes): Searchable
{
    return Searchable::create(array_merge(['source' => 'content:t', 'url' => '/x'], $attributes));
}

beforeEach(function () {
    setUpSearchable();
    $this->actingAs((new User)->forceFill(['id' => 1]));
});

it('matches title, description and keywords case-insensitively', function () {
    searchableRow(['key' => '1', 'title' => 'Users']);
    searchableRow(['key' => '2', 'title' => 'Roles', 'description' => 'Manage USER roles']);
    searchableRow(['key' => '3', 'title' => 'Staff', 'keywords' => 'user member']);
    searchableRow(['key' => '4', 'title' => 'Invoices']);

    expect(SynSearchable::search('user')->pluck('key')->sort()->values()->all())->toBe(['1', '2', '3']);
});

it('ranks title-prefix, then title-contains, then other matches', function () {
    searchableRow(['key' => 'desc', 'title' => 'Zeta', 'description' => 'about users']);
    searchableRow(['key' => 'contains', 'title' => 'All users']);
    searchableRow(['key' => 'prefix', 'title' => 'Users']);

    expect(SynSearchable::search('users')->pluck('key')->all())->toBe(['prefix', 'contains', 'desc']);
});

it('returns current-locale and locale-less rows only', function () {
    app()->setLocale('id');
    searchableRow(['key' => 'en', 'locale' => 'en', 'title' => 'Invoice']);
    searchableRow(['key' => 'id', 'locale' => 'id', 'title' => 'Invoice ID']);
    searchableRow(['key' => 'all', 'title' => 'Invoice all']);

    expect(SynSearchable::search('invoice')->pluck('key')->sort()->values()->all())->toBe(['all', 'id']);
});

it('treats % and _ in the term literally', function () {
    searchableRow(['key' => '1', 'title' => '100% done']);
    searchableRow(['key' => '2', 'title' => '1000 done']);

    expect(SynSearchable::search('0%')->pluck('key')->all())->toBe(['1']);
});

it('filters by acl through the Gate and fills up to the limit', function () {
    Gate::define('allowed.ability', fn () => true);
    Gate::define('denied.ability', fn () => false);
    searchableRow(['key' => 'denied1', 'title' => 'Report A', 'acl' => 'denied.ability']);
    searchableRow(['key' => 'denied2', 'title' => 'Report B', 'acl' => 'denied.ability']);
    searchableRow(['key' => 'ok', 'title' => 'Report C', 'acl' => 'allowed.ability']);
    searchableRow(['key' => 'open', 'title' => 'Report D']);

    expect(SynSearchable::search('report', 2)->pluck('key')->all())->toBe(['ok', 'open']);
});

it('checks acl through the user can() method, like @canAccess does', function () {
    // synapps-auth overrides User::can() without defining Gate abilities.
    $user = new class extends User
    {
        public function can($abilities, $arguments = [])
        {
            return $abilities === 'custom.allowed';
        }
    };
    $this->actingAs($user->forceFill(['id' => 2]));
    searchableRow(['key' => 'ok', 'title' => 'Report A', 'acl' => 'custom.allowed']);
    searchableRow(['key' => 'no', 'title' => 'Report B', 'acl' => 'custom.denied']);

    expect(SynSearchable::search('report')->pluck('key')->all())->toBe(['ok']);
});

it('hides acl rows from guests', function () {
    auth()->logout();
    Gate::define('allowed.ability', fn () => true);
    searchableRow(['key' => 'acl', 'title' => 'Report', 'acl' => 'allowed.ability']);
    searchableRow(['key' => 'open', 'title' => 'Report open']);

    expect(SynSearchable::search('report')->pluck('key')->all())->toBe(['open']);
});

it('skips rows whose route no longer exists', function () {
    searchableRow(['key' => 'stale', 'title' => 'Gone', 'route' => 'gone.index', 'url' => null]);
    searchableRow(['key' => 'live', 'title' => 'Gone but url']);

    expect(SynSearchable::search('gone')->pluck('key')->all())->toBe(['live']);
});

it('returns nothing for a blank term', function () {
    searchableRow(['key' => '1', 'title' => 'Users']);

    expect(SynSearchable::search('   '))->toBeEmpty();
});
