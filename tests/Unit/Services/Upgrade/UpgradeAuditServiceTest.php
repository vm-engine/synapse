<?php

use VmEngine\Synapse\Services\Upgrade\UpgradeAuditService;

beforeEach(function () {
    $this->fixtureRoot = sys_get_temp_dir().'/synapse-upgrade-audit-'.uniqid();
    mkdir($this->fixtureRoot.'/resources/views', 0777, true);
    $this->service = new UpgradeAuditService;
});

afterEach(function () {
    if (is_dir($this->fixtureRoot)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->fixtureRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->fixtureRoot);
    }
});

function writeFixtureFile(string $root, string $relativePath, string $contents): void
{
    $path = $root.'/'.$relativePath;
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, $contents);
}

it('detects the dot-notation blade tag (rule A) and leaves namespaced tags alone', function () {
    writeFixtureFile($this->fixtureRoot, 'resources/views/example.blade.php', <<<'BLADE'
        <livewire:example.todo-form />
        <livewire:example::already-namespaced />
        BLADE);

    $report = $this->service->scan($this->fixtureRoot);

    $ruleAFindings = array_filter($report->findings, fn ($f) => $f->ruleId === 'A');

    expect($ruleAFindings)->toHaveCount(1);
    expect(array_values($ruleAFindings)[0]->line)->toBe(1);
});

it('fixes the dot-notation blade tag', function () {
    writeFixtureFile($this->fixtureRoot, 'resources/views/example.blade.php', '<livewire:example.todo-form />');

    $applyReport = $this->service->apply($this->fixtureRoot, ['A']);

    expect($applyReport->appliedCount())->toBe(1);
    expect(file_get_contents($this->fixtureRoot.'/resources/views/example.blade.php'))
        ->toBe('<livewire:example::todo-form />');
    expect(file_exists($this->fixtureRoot.'/resources/views/example.blade.php.bak'))->toBeTrue();
});

it('detects and fixes Route::livewire() dotted string targets (rule B)', function () {
    writeFixtureFile($this->fixtureRoot, 'routes/web.php', "<?php\nRoute::livewire('/todo', 'example.todo-form');\n");

    $report = $this->service->scan($this->fixtureRoot, ['B']);
    expect($report->findings)->toHaveCount(1);

    $this->service->apply($this->fixtureRoot, ['B']);
    expect(file_get_contents($this->fixtureRoot.'/routes/web.php'))
        ->toContain("Route::livewire('/todo', 'example::todo-form');");
});

it('detects and fixes the removed register methods (rule C)', function () {
    writeFixtureFile($this->fixtureRoot, 'Provider.php', <<<'PHP'
        <?php
        class Provider {
            public function register(): void
            {
                $this->registerMfcComponents();
                $this->registerLivewireComponents();
            }
        }
        PHP);

    $report = $this->service->scan($this->fixtureRoot, ['C']);
    expect($report->findings)->toHaveCount(2);

    $this->service->apply($this->fixtureRoot, ['C']);
    $fixed = file_get_contents($this->fixtureRoot.'/Provider.php');

    expect($fixed)->not->toContain('registerMfcComponents');
    expect($fixed)->not->toContain('registerLivewireComponents');
    expect(substr_count($fixed, 'registerLivewireNamespace();'))->toBe(1);
});

it('flags Livewire.hook() JS calls as manual review only (rule D)', function () {
    writeFixtureFile($this->fixtureRoot, 'resources/js/app.js', "Livewire.hook('commit', ({ component, commit }) => {});\n");

    $report = $this->service->scan($this->fixtureRoot, ['D']);

    expect($report->findings)->toHaveCount(1);
    expect($report->findings[0]->autoFixable)->toBeFalse();
    expect($report->autoFixable())->toBeEmpty();
});

it('flags Route::get(Component::class) candidates as manual review only (rule E)', function () {
    writeFixtureFile($this->fixtureRoot, 'routes/web.php', "<?php\nRoute::get('/dashboard', Dashboard::class);\n");

    $report = $this->service->scan($this->fixtureRoot, ['E']);

    expect($report->findings)->toHaveCount(1);
    expect($report->findings[0]->autoFixable)->toBeFalse();
});

it('apply() never touches files with only manual-review findings', function () {
    writeFixtureFile($this->fixtureRoot, 'resources/js/app.js', "Livewire.hook('commit', () => {});\n");
    $original = file_get_contents($this->fixtureRoot.'/resources/js/app.js');

    $applyReport = $this->service->apply($this->fixtureRoot);

    expect($applyReport->appliedCount())->toBe(0);
    expect(file_get_contents($this->fixtureRoot.'/resources/js/app.js'))->toBe($original);
    expect(file_exists($this->fixtureRoot.'/resources/js/app.js.bak'))->toBeFalse();
});

it('reports unknown version when composer.lock is missing', function () {
    $report = $this->service->scan($this->fixtureRoot);

    expect($report->installedVersion)->toContain('unknown');
});

it('detects the installed vm-engine/synapse version from composer.lock', function () {
    writeFixtureFile($this->fixtureRoot, 'composer.lock', json_encode([
        'packages' => [
            ['name' => 'vm-engine/synapse', 'version' => 'v2.0.3'],
        ],
    ]));

    $report = $this->service->scan($this->fixtureRoot);

    expect($report->installedVersion)->toBe('v2.0.3');
});

it('renders a markdown report grouping auto-fixable and manual-review findings', function () {
    writeFixtureFile($this->fixtureRoot, 'resources/views/example.blade.php', '<livewire:example.todo-form />');
    writeFixtureFile($this->fixtureRoot, 'resources/js/app.js', "Livewire.hook('commit', () => {});\n");

    $report = $this->service->scan($this->fixtureRoot);
    $markdown = $report->toMarkdown();

    expect($markdown)->toContain('Breaking changes found (auto-fixable)');
    expect($markdown)->toContain('Manual review required');
    expect($markdown)->toContain('blade-tag-notation');
    expect($markdown)->toContain('js-livewire-hooks');
});
