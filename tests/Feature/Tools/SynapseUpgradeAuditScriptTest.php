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

it('skips excluded and unreadable directories instead of aborting', function () {
    file_put_contents($this->fixtureRoot.'/resources/views/example.blade.php', '<livewire:example.todo-form />');
    foreach (['vendor/acme', 'resources/locked'] as $dir) {
        mkdir($this->fixtureRoot.'/'.$dir, 0777, true);
        file_put_contents($this->fixtureRoot.'/'.$dir.'/view.blade.php', '<livewire:example.todo-form />');
    }
    chmod($this->fixtureRoot.'/vendor', 0000);
    chmod($this->fixtureRoot.'/resources/locked', 0000);

    try {
        exec(sprintf('php %s --path=%s 2>&1', escapeshellarg($this->scriptPath), escapeshellarg($this->fixtureRoot)), $output, $exitCode);
    } finally {
        chmod($this->fixtureRoot.'/vendor', 0755);
        chmod($this->fixtureRoot.'/resources/locked', 0755);
    }

    expect($exitCode)->toBe(0)
        ->and(file_get_contents($this->fixtureRoot.'/SYNAPSE_UPGRADE_AUDIT.md'))
        ->toContain('resources/views/example.blade.php')
        ->not->toContain('vendor/acme')
        ->not->toContain('resources/locked');
});

it('flags @themeScripts rendered after </head> (rule F)', function () {
    mkdir($this->fixtureRoot.'/synapps/resources/themes/default/layouts', 0777, true);
    file_put_contents($this->fixtureRoot.'/synapps/resources/themes/default/layouts/app.blade.php', "<html>\n<head>\n</head>\n<body>\n    @themeScripts('app')\n</body>\n</html>\n");
    file_put_contents($this->fixtureRoot.'/synapps/resources/themes/default/layouts/guest.blade.php', "<html>\n<head>\n    @themeScripts('guest')\n</head>\n<body></body>\n</html>\n");

    exec(sprintf('php %s --path=%s 2>&1', escapeshellarg($this->scriptPath), escapeshellarg($this->fixtureRoot)), $output, $exitCode);
    $report = (string) file_get_contents($this->fixtureRoot.'/SYNAPSE_UPGRADE_AUDIT.md');

    expect($exitCode)->toBe(0)
        ->and($report)->toContain('theme-scripts-in-body')
        ->toContain('layouts/app.blade.php')
        ->not->toContain('layouts/guest.blade.php');
});
