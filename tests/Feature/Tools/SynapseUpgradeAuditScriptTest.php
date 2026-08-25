<?php

declare(strict_types=1);

beforeEach(function () {
    $this->fixtureRoot = sys_get_temp_dir().'/synapse-upgrade-audit-script-'.uniqid();
    mkdir($this->fixtureRoot.'/resources/views', 0777, true);
    $this->scriptPath = dirname(__DIR__, 3).'/tools/synapse-upgrade-audit.php';
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

it('is a self-contained file with no require/include statements', function () {
    $contents = file_get_contents($this->scriptPath);

    expect($contents)->not->toContain('require ');
    expect($contents)->not->toContain('require_once');
    expect($contents)->not->toContain('include ');
    expect($contents)->not->toContain('include_once');
});

it('scans a target project and writes a markdown report without modifying files', function () {
    file_put_contents($this->fixtureRoot.'/resources/views/example.blade.php', '<livewire:example.todo-form />');
    file_put_contents($this->fixtureRoot.'/routes.php', "<?php\nRoute::livewire('/todo', 'example.todo-form');\nRoute::get('/dashboard', Dashboard::class);\n");
    file_put_contents($this->fixtureRoot.'/Provider.php', "<?php\nclass Provider {\n    public function register(): void\n    {\n        \$this->registerMfcComponents();\n        \$this->registerLivewireComponents();\n    }\n}\n");
    file_put_contents($this->fixtureRoot.'/app.js', "Livewire.hook('commit', ({ component, commit }) => {});\n");

    $originalBlade = file_get_contents($this->fixtureRoot.'/resources/views/example.blade.php');

    $command = sprintf('php %s --path=%s 2>&1', escapeshellarg($this->scriptPath), escapeshellarg($this->fixtureRoot));
    exec($command, $output, $exitCode);

    expect($exitCode)->toBe(0);

    $reportPath = $this->fixtureRoot.'/SYNAPSE_UPGRADE_AUDIT.md';
    expect(file_exists($reportPath))->toBeTrue();

    $report = file_get_contents($reportPath);
    expect($report)->toContain('blade-tag-notation');
    expect($report)->toContain('route-livewire-notation');
    expect($report)->toContain('register-methods');
    expect($report)->toContain('js-livewire-hooks');
    expect($report)->toContain('route-get-livewire-candidate');

    // No source files were modified.
    expect(file_get_contents($this->fixtureRoot.'/resources/views/example.blade.php'))->toBe($originalBlade);
});

it('respects a custom --output path', function () {
    file_put_contents($this->fixtureRoot.'/resources/views/example.blade.php', '<livewire:example.todo-form />');

    $command = sprintf(
        'php %s --path=%s --output=custom-report.md 2>&1',
        escapeshellarg($this->scriptPath),
        escapeshellarg($this->fixtureRoot)
    );
    exec($command, $output, $exitCode);

    expect($exitCode)->toBe(0);
    expect(file_exists($this->fixtureRoot.'/custom-report.md'))->toBeTrue();
});
