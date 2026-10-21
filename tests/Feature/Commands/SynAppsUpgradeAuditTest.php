<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Runs in a private base path: the audit walks the whole project root, and other test files
 * (e.g. SynAppsPatchResourcesSplitTest) create and delete directories in the shared Testbench
 * root under --parallel — a directory vanishing mid-walk made RecursiveDirectoryIterator throw.
 */
beforeEach(function () {
    $this->originalBasePath = base_path();
    $this->sandbox = sys_get_temp_dir().'/synapse-upgrade-audit-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->sandbox.'/resources/views');
    app()->setBasePath($this->sandbox);

    $this->reportPath = base_path('SYNAPSE_UPGRADE_AUDIT.md');
});

afterEach(function () {
    app()->setBasePath($this->originalBasePath);
    File::deleteDirectory($this->sandbox);
});

it('reports findings without writing changes by default', function () {
    File::put(base_path('resources/views/upgrade-audit-fixture.blade.php'), '<livewire:example.todo-form />');

    $original = File::get(base_path('resources/views/upgrade-audit-fixture.blade.php'));

    $this->artisan('synapps:upgrade-audit')
        ->assertExitCode(0);

    expect(File::exists($this->reportPath))->toBeTrue();
    expect(File::get($this->reportPath))->toContain('blade-tag-notation');
    expect(File::get(base_path('resources/views/upgrade-audit-fixture.blade.php')))->toBe($original);
    expect(File::exists(base_path('resources/views/upgrade-audit-fixture.blade.php.bak')))->toBeFalse();
});

it('applies auto-fixable findings and writes a backup when --apply --force is passed', function () {
    File::put(base_path('resources/views/upgrade-audit-fixture.blade.php'), '<livewire:example.todo-form />');

    $this->artisan('synapps:upgrade-audit', ['--apply' => true, '--force' => true])
        ->assertExitCode(0);

    expect(File::get(base_path('resources/views/upgrade-audit-fixture.blade.php')))
        ->toBe('<livewire:example::todo-form />');
    expect(File::exists(base_path('resources/views/upgrade-audit-fixture.blade.php.bak')))->toBeTrue();
});

it('leaves manual-review-only findings untouched even with --apply', function () {
    File::put(base_path('upgrade-audit-fixture-routes.php'), "<?php\nRoute::get('/dashboard', Dashboard::class);\n");
    $original = File::get(base_path('upgrade-audit-fixture-routes.php'));

    $this->artisan('synapps:upgrade-audit', ['--apply' => true, '--force' => true])
        ->assertExitCode(0);

    expect(File::get(base_path('upgrade-audit-fixture-routes.php')))->toBe($original);
});

it('restricts to specific rule IDs via --only', function () {
    File::put(base_path('resources/views/upgrade-audit-fixture.blade.php'), '<livewire:example.todo-form />');
    File::put(base_path('upgrade-audit-fixture-routes.php'), "<?php\nRoute::livewire('/todo', 'example.todo-form');\n");

    $this->artisan('synapps:upgrade-audit', ['--apply' => true, '--force' => true, '--only' => ['B']])
        ->assertExitCode(0);

    // Rule B fixed
    expect(File::get(base_path('upgrade-audit-fixture-routes.php')))
        ->toContain("'example::todo-form'");

    // Rule A left alone since --only=B was passed
    expect(File::get(base_path('resources/views/upgrade-audit-fixture.blade.php')))
        ->toBe('<livewire:example.todo-form />');
});
