<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use VmEngine\Synapse\Models\Searchable;

beforeEach(function () {
    app('translator')->addLines(['menu.users' => 'Users', 'menu.admin' => 'Admin', 'search.create' => 'Create user'], 'en');
    app('translator')->addLines(['menu.users' => 'Pengguna', 'menu.admin' => 'Admin ID', 'search.create' => 'Buat pengguna'], 'id');

    config([
        'app.locale' => 'en',
        'synapps.apps.multi_language' => true,
        'synapps.apps.enabled_languages' => ['en', 'id'],
        'synapps.nav.backend' => [
            'home' => ['name' => 'Dashboard', 'route' => 'home.dashboard', 'icon' => 'ph ph-house'],
            'admin' => [
                'name' => '__menu.admin',
                'acl' => 'admin.any',
                'sub' => [
                    'users' => ['id' => 'users', 'name' => '__menu.users', 'route' => 'auth.users', 'icon' => 'ph ph-users'],
                    'roles' => ['id' => 'roles', 'name' => 'Roles', 'route' => 'auth.roles', 'acl' => 'auth.roles'],
                ],
            ],
        ],
        'synapps.modules' => [
            'auth' => [
                'id' => 'auth',
                'searchables' => [
                    'create' => ['name' => '__search.create', 'route' => 'auth.users.create', 'acl' => 'auth.users', 'keywords' => 'new add'],
                    'broken' => ['description' => 'no name, no route'],
                ],
            ],
        ],
    ]);
});

it('creates the table and imports nav and module entries per locale', function () {
    $this->artisan('synapps:searchable-setup')
        ->expectsOutputToContain('Skipped module searchable [auth.broken]')
        ->assertExitCode(0);

    expect(Schema::hasTable('searchables'))->toBeTrue()
        // home, admin.users, admin.roles, auth.create × 2 locales; admin (no route) skipped
        ->and(Searchable::count())->toBe(8);

    $users = Searchable::where('key', 'admin.users')->get()->keyBy('locale');
    expect($users['en']->title)->toBe('Users')
        ->and($users['id']->title)->toBe('Pengguna')
        ->and($users['en']->description)->toBe('Admin')
        ->and($users['id']->description)->toBe('Admin ID')
        ->and($users['en']->source)->toBe('nav')
        ->and($users['en']->acl)->toBe('admin.any');

    expect(Searchable::where('key', 'admin.roles')->first()->acl)->toBe('auth.roles');

    $create = Searchable::where('key', 'auth.create')->where('locale', 'id')->first();
    expect($create->source)->toBe('module:auth')
        ->and($create->title)->toBe('Buat pengguna')
        ->and($create->keywords)->toBe('new add');
});

it('uses only app.locale when multi_language is off', function () {
    config(['synapps.apps.multi_language' => false]);

    $this->artisan('synapps:searchable-setup')->assertExitCode(0);

    expect(Searchable::distinct()->pluck('locale')->all())->toBe(['en']);
});

it('is idempotent and never touches content rows', function () {
    $this->artisan('synapps:searchable-setup')->assertExitCode(0);
    Searchable::create(['source' => 'content:example.item', 'key' => '1', 'title' => 'Acme', 'url' => '/a']);

    $this->artisan('synapps:searchable-setup')->assertExitCode(0);

    expect(Searchable::count())->toBe(9)
        ->and(Searchable::where('source', 'content:example.item')->exists())->toBeTrue();
});
