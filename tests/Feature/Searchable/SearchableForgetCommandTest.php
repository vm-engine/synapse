<?php

declare(strict_types=1);

use VmEngine\Synapse\Models\Searchable;

function seedForgetRows(): void
{
    Searchable::create(['source' => 'content:example.item', 'key' => '1', 'title' => 'A', 'url' => '/a']);
    Searchable::create(['source' => 'content:example.category', 'key' => '1', 'title' => 'B', 'url' => '/b']);
    Searchable::create(['source' => 'content:other', 'key' => '1', 'title' => 'C', 'url' => '/c']);
}

it('reports when searchable is not set up', function () {
    $this->artisan('synapps:searchable-forget', ['prefix' => 'content:example'])
        ->expectsOutputToContain('Searchable is not set up')
        ->assertExitCode(0);
});

it('rejects a prefix outside content:', function () {
    setUpSearchable();

    $this->artisan('synapps:searchable-forget', ['prefix' => 'nav'])
        ->expectsOutputToContain('must start with "content:"')
        ->assertExitCode(1);
});

it('asks before deleting and keeps rows when declined', function () {
    setUpSearchable();
    seedForgetRows();

    $this->artisan('synapps:searchable-forget', ['prefix' => 'content:example'])
        ->expectsConfirmation('Delete 2 searchable entries matching [content:example*]?', 'no')
        ->assertExitCode(0);

    expect(Searchable::count())->toBe(3);
});

it('deletes matching rows when confirmed', function () {
    setUpSearchable();
    seedForgetRows();

    $this->artisan('synapps:searchable-forget', ['prefix' => 'content:example'])
        ->expectsConfirmation('Delete 2 searchable entries matching [content:example*]?', 'yes')
        ->expectsOutputToContain('Deleted 2')
        ->assertExitCode(0);

    expect(Searchable::pluck('source')->all())->toBe(['content:other']);
});

it('skips the prompt with --force', function () {
    setUpSearchable();
    seedForgetRows();

    $this->artisan('synapps:searchable-forget', ['prefix' => 'content:', '--force' => true])
        ->assertExitCode(0);

    expect(Searchable::count())->toBe(0);
});

it('says so when nothing matches', function () {
    setUpSearchable();

    $this->artisan('synapps:searchable-forget', ['prefix' => 'content:none'])
        ->expectsOutputToContain('No searchable entries match')
        ->assertExitCode(0);
});
