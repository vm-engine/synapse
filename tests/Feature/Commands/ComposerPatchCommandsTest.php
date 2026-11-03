<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any test files
    $this->composerPath = base_path('composer.json');
    $this->composerLocalPath = base_path('composer.local.json');
    $this->composerOriginalPath = base_path('composer.original.json');
    $this->gitignorePath = base_path('.gitignore');

    // Backup real composer.json
    if (File::exists($this->composerPath)) {
        $this->realComposerBackup = File::get($this->composerPath);
    }
});

afterEach(function () {
    // Clean up test files
    if (File::exists($this->composerLocalPath)) {
        File::delete($this->composerLocalPath);
    }

    if (File::exists($this->composerOriginalPath)) {
        File::delete($this->composerOriginalPath);
    }

    // Restore real composer.json
    if (isset($this->realComposerBackup)) {
        File::put($this->composerPath, $this->realComposerBackup);
    }
});

it('requires composer.local.json to exist', function () {
    // Ensure composer.local.json doesn't exist
    if (File::exists($this->composerLocalPath)) {
        File::delete($this->composerLocalPath);
    }

    $this->artisan('synapse:patch-composer')
        ->assertExitCode(1);
});

it('patches composer.json with composer.local.json', function () {
    // Create a sample composer.json
    $composerContent = [
        'name' => 'test/project',
        'require' => [
            'php' => '^8.3',
        ],
    ];
    File::put($this->composerPath, json_encode($composerContent, JSON_PRETTY_PRINT));

    // Create composer.local.json
    $composerLocalContent = [
        'repositories' => [
            ['type' => 'path', 'url' => './packages/test'],
        ],
        'require' => [
            'test/package' => '@dev',
        ],
    ];
    File::put($this->composerLocalPath, json_encode($composerLocalContent, JSON_PRETTY_PRINT));

    $this->artisan('synapse:patch-composer')
        ->assertExitCode(0);

    // Verify backup was created
    expect(File::exists($this->composerOriginalPath))->toBeTrue();

    // Verify merge was successful
    $patchedComposer = json_decode(File::get($this->composerPath), true);
    expect($patchedComposer['repositories'])->toHaveCount(1);
    expect($patchedComposer['require'])->toHaveKey('test/package');
    expect($patchedComposer['require']['php'])->toBe('^8.3');
});

it('updates gitignore with required entries', function () {
    // Create composer.local.json
    $composerLocalContent = ['require' => ['test/package' => '@dev']];
    File::put($this->composerLocalPath, json_encode($composerLocalContent));

    $this->artisan('synapse:patch-composer')
        ->assertExitCode(0);

    $gitignoreContent = File::get($this->gitignorePath);
    expect($gitignoreContent)->toContain('composer.local.json');
    expect($gitignoreContent)->toContain('composer.original.json');
});

it('restores original composer.json', function () {
    // Create original composer.json
    $originalContent = ['name' => 'test/original'];
    File::put($this->composerPath, json_encode($originalContent, JSON_PRETTY_PRINT));

    // Create backup
    File::copy($this->composerPath, $this->composerOriginalPath);

    // Modify composer.json
    $modifiedContent = ['name' => 'test/modified'];
    File::put($this->composerPath, json_encode($modifiedContent, JSON_PRETTY_PRINT));

    // Restore
    $this->artisan('synapse:restore-composer')
        ->expectsConfirmation('Do you want to continue?', 'yes')
        ->assertExitCode(0);

    // Verify restoration
    $restoredComposer = json_decode(File::get($this->composerPath), true);
    expect($restoredComposer['name'])->toBe('test/original');

    // Verify backup was removed
    expect(File::exists($this->composerOriginalPath))->toBeFalse();
});

it('fails to restore if backup does not exist', function () {
    // Ensure backup doesn't exist
    if (File::exists($this->composerOriginalPath)) {
        File::delete($this->composerOriginalPath);
    }

    $this->artisan('synapse:restore-composer')
        ->assertExitCode(1);
});

it('deep merges nested arrays', function () {
    // Create composer.json with nested structure
    $composerContent = [
        'require' => [
            'php' => '^8.3',
            'laravel/framework' => '^12.0',
        ],
        'autoload' => [
            'psr-4' => [
                'App\\' => 'app/',
            ],
        ],
    ];
    File::put($this->composerPath, json_encode($composerContent, JSON_PRETTY_PRINT));

    // Create composer.local.json with additional nested values
    $composerLocalContent = [
        'require' => [
            'test/package' => '@dev',
        ],
        'autoload' => [
            'psr-4' => [
                'Tests\\' => 'tests/',
            ],
        ],
    ];
    File::put($this->composerLocalPath, json_encode($composerLocalContent, JSON_PRETTY_PRINT));

    $this->artisan('synapse:patch-composer')
        ->assertExitCode(0);

    // Verify deep merge
    $patchedComposer = json_decode(File::get($this->composerPath), true);

    // Check that both original and new require entries exist
    expect($patchedComposer['require'])->toHaveKey('php');
    expect($patchedComposer['require'])->toHaveKey('laravel/framework');
    expect($patchedComposer['require'])->toHaveKey('test/package');

    // Check that both original and new autoload entries exist
    expect($patchedComposer['autoload']['psr-4'])->toHaveKey('App\\');
    expect($patchedComposer['autoload']['psr-4'])->toHaveKey('Tests\\');
});

it('appends list entries instead of merging them by index', function () {
    File::put($this->composerPath, json_encode([
        'repositories' => [
            ['type' => 'vcs', 'url' => 'https://github.com/vm-engine/synmod-region'],
            ['type' => 'vcs', 'url' => 'https://github.com/vm-engine/synapse'],
        ],
    ], JSON_PRETTY_PRINT));

    File::put($this->composerLocalPath, json_encode([
        'repositories' => [
            ['type' => 'path', 'url' => './packages/synmod-crm', 'options' => ['symlink' => true]],
        ],
    ], JSON_PRETTY_PRINT));

    $this->artisan('synapse:patch-composer')->assertExitCode(0);

    $repositories = json_decode(File::get($this->composerPath), true)['repositories'];

    expect($repositories)->toHaveCount(3)
        ->and($repositories[0])->toBe(['type' => 'vcs', 'url' => 'https://github.com/vm-engine/synmod-region'])
        ->and($repositories[2])->toBe(['type' => 'path', 'url' => './packages/synmod-crm', 'options' => ['symlink' => true]]);
});

it('does not duplicate list entries when re-patched', function () {
    File::put($this->composerPath, json_encode([
        'repositories' => [['type' => 'path', 'url' => './packages/synmod-crm']],
    ], JSON_PRETTY_PRINT));

    File::put($this->composerLocalPath, json_encode([
        'repositories' => [['type' => 'path', 'url' => './packages/synmod-crm']],
    ], JSON_PRETTY_PRINT));

    $this->artisan('synapse:patch-composer')->assertExitCode(0);

    expect(json_decode(File::get($this->composerPath), true)['repositories'])->toHaveCount(1);
});
