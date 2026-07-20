<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->reportPath = base_path('SYNAPSE_UPGRADE_AUDIT.md');
    $this->fixtureFiles = [
        base_path('resources/views/upgrade-audit-fixture.blade.php'),
        base_path('upgrade-audit-fixture-routes.php'),
    ];
});

afterEach(function () {
    if (File::exists($this->reportPath)) {
        File::delete($this->reportPath);
    }

    foreach ($this->fixtureFiles as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
        if (File::exists($file.'.bak')) {
            File::delete($file.'.bak');
        }
    }
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
