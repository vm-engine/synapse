<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Tests for SynAppsPatchResources layouts operations.
 *
 * Files are created in the Testbench Laravel root (base_path()) and cleaned up after each test.
 */
beforeEach(function () {
    // Ensure required directories exist in testbench laravel root
    File::makeDirectory(resource_path('css'), 0755, true, true);
    File::makeDirectory(resource_path('js'), 0755, true, true);
    File::makeDirectory(base_path('synapps/resources/css'), 0755, true, true);
    File::makeDirectory(base_path('synapps/resources/js'), 0755, true, true);
    File::makeDirectory(base_path('synapps/resources/themes/default/css'), 0755, true, true);
    File::makeDirectory(base_path('synapps/resources/themes/default/js'), 0755, true, true);

    // Write a minimal monolith app.css for tests that need it
    $this->monolithCss = <<<'CSS'
        @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap") layer(base);
        @import "tailwindcss";
        @import '../../synapps/resources/css/synapps.css';
        @import '../../synapps/resources/themes/default/css/theme.css';
        @source '../../storage/framework/views/*.php';
        @source '../**/*.blade.php';
        @theme {
            --font-sans: "Instrument Sans";
        }
        CSS;

    $this->monolithJs = <<<'JS'
        import '../../synapps/resources/js/synapps.js';
        import '../../synapps/resources/themes/default/js/theme.js';

        // Synapse global helpers
        window.withBack = (url) => url;
        JS;

    // Set active theme config
    config(['synapps.apps.themes.active' => 'default']);
});

afterEach(function () {
    // Clean up all test-generated files
    $toDelete = [
        resource_path('css/base.css'),
        resource_path('css/app.css'),
        resource_path('css/frontend.css'),
        resource_path('css/guest.css'),
        resource_path('css/app.css.bak'),
        resource_path('js/base.js'),
        resource_path('js/app.js'),
        resource_path('js/frontend.js'),
        resource_path('js/guest.js'),
        resource_path('js/app.js.bak'),
        base_path('synapps/resources/js/synapse-components.js'),
        base_path('synapps/resources/js/synapps.js'),
        base_path('synapps/resources/css/synapps-base.css'),
        base_path('synapps/resources/css/synapps-app.css'),
        base_path('synapps/resources/css/synapps-frontend.css'),
        base_path('synapps/resources/css/synapps-guest.css'),
        base_path('synapps/resources/css/tailwind-ref.css'),
        base_path('synapps/resources/js/synapps-base.js'),
        base_path('synapps/resources/js/synapps-app.js'),
        base_path('synapps/resources/js/synapps-frontend.js'),
        base_path('synapps/resources/js/synapps-guest.js'),
        base_path('vite.config.js'),
        base_path('vite.config.js.bak'),
    ];

    foreach ($toDelete as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Remove only the synapps dirs this file owns — deleting base_path('synapps')
    // wholesale races other parallel workers' fixtures under synapps_path().
    foreach (['resources/css', 'resources/js', 'resources/themes/default'] as $dir) {
        File::deleteDirectory(base_path("synapps/{$dir}"));
    }
});

// ─── createSynappsIntermediaryCss ────────────────────────────────────────────

test('creates synapps-base.css with correct imports', function () {
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('synapps/resources/css/synapps-base.css'));

    expect($content)->toContain("@import './synapps.css';")
        ->and($content)->toContain("@import '../themes/default/css/theme-base.css';");
});

test('creates synapps-app.css with correct theme import', function () {
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('synapps/resources/css/synapps-app.css'));

    expect($content)->toContain("@import '../themes/default/css/theme-app.css';");
});

test('creates synapps-frontend.css and synapps-guest.css', function () {
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::exists(base_path('synapps/resources/css/synapps-frontend.css')))->toBeTrue()
        ->and(File::exists(base_path('synapps/resources/css/synapps-guest.css')))->toBeTrue();
});

test('creates synapps-base.js with correct imports', function () {
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('synapps/resources/js/synapps-base.js'));

    expect($content)->toContain("import './synapps.js';")
        ->and($content)->toContain("import '../themes/default/js/theme-base.js';");
});

test('creates synapps-app.js and layout-specific js files', function () {
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::exists(base_path('synapps/resources/js/synapps-app.js')))->toBeTrue()
        ->and(File::exists(base_path('synapps/resources/js/synapps-frontend.js')))->toBeTrue()
        ->and(File::exists(base_path('synapps/resources/js/synapps-guest.js')))->toBeTrue();
});

// ─── createBaseCss / rewriteAppCssToFocused ───────────────────────────────────

test('creates base.css from monolith app.css and rewrites app.css', function () {
    File::put(resource_path('css/app.css'), $this->monolithCss);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::exists(resource_path('css/base.css')))->toBeTrue();

    $baseCss = File::get(resource_path('css/base.css'));
    expect($baseCss)->toContain('@import "tailwindcss"')
        ->and($baseCss)->toContain("@import '../../synapps/resources/css/synapps-base.css';")
        ->and($baseCss)->not->toContain('theme.css')  // direct theme.css removed
        ->and($baseCss)->not->toContain("@import '../../synapps/resources/css/synapps.css';");  // replaced

    $appCss = File::get(resource_path('css/app.css'));
    expect($appCss)->toBe("@reference '../../synapps/resources/css/tailwind-ref.css';\n@import '../../synapps/resources/css/synapps-app.css';\n")
        ->and($appCss)->not->toContain('@import "tailwindcss"');
});

test('creates frontend.css and guest.css layout entry files', function () {
    File::put(resource_path('css/app.css'), $this->monolithCss);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get(resource_path('css/frontend.css')))
        ->toBe("@reference '../../synapps/resources/css/tailwind-ref.css';\n@import '../../synapps/resources/css/synapps-frontend.css';\n");

    expect(File::get(resource_path('css/guest.css')))
        ->toBe("@reference '../../synapps/resources/css/tailwind-ref.css';\n@import '../../synapps/resources/css/synapps-guest.css';\n");
});

// ─── createTailwindRefCss ─────────────────────────────────────────────────────

test('creates tailwind-ref.css with tailwind import and theme tokens', function () {
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $path = base_path('synapps/resources/css/tailwind-ref.css');
    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)->toContain('@import "tailwindcss";')
        ->and($content)->toContain("@import './synapse-components.css';")
        ->and($content)->toContain("@import '../themes/default/css/theme-base.css';")
        ->and($content)->not->toContain("@source '");
});

test('does not overwrite existing tailwind-ref.css', function () {
    File::put(base_path('synapps/resources/css/tailwind-ref.css'), "/* custom */\n");

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get(base_path('synapps/resources/css/tailwind-ref.css')))->toBe("/* custom */\n");
});

// ─── migrateLtaCssToComponentsCss ─────────────────────────────────────────────

test('migrates lta.css to synapse-components.css on an already-set-up project', function () {
    $cssDir = base_path('synapps/resources/css');
    File::put($cssDir.'/lta.css', "@theme {\n    --color-brand-500: #465fff;\n}\n");
    File::put($cssDir.'/synapps.css', "@import \"@fortawesome/fontawesome-free/css/all.min.css\";\n@import \"./lta.css\";\n");
    File::put($cssDir.'/tailwind-ref.css', "@import \"tailwindcss\";\n@import './lta.css';\n@import '../themes/default/css/theme-base.css';\n");

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::exists($cssDir.'/lta.css'))->toBeFalse()
        ->and(File::exists($cssDir.'/synapse-components.css'))->toBeTrue()
        ->and(File::get($cssDir.'/synapps.css'))->toContain("@import './synapse-components.css';")
        ->and(File::get($cssDir.'/tailwind-ref.css'))->toContain("@import './synapse-components.css';");
});

test('does not re-migrate a project that already migrated', function () {
    $cssDir = base_path('synapps/resources/css');
    File::put($cssDir.'/synapse-components.css', "/* already migrated */\n");
    File::put($cssDir.'/synapps.css', "@import \"./synapse-components.css\";\n");

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get($cssDir.'/synapse-components.css'))->toBe("/* already migrated */\n");
});

// ─── installComponentsJs ───────────────────────────────────────────────────────

test('installs synapse-components.js and imports it from synapps.js', function () {
    $jsDir = base_path('synapps/resources/js');
    File::put($jsDir.'/synapps.js', "import \"flatpickr/dist/flatpickr.min.css\";\nimport flatpickr from \"flatpickr\";\n\nAlpine.plugin(mask);\n");

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get($jsDir.'/synapse-components.js'))->toContain("Alpine.data('synapseModal'")
        ->and(File::get($jsDir.'/synapps.js'))->toContain("import flatpickr from \"flatpickr\";\nimport \"./synapse-components.js\";")
        ->and(substr_count(File::get($jsDir.'/synapps.js'), 'synapse-components.js'))->toBe(1);
});

test('prepends the import when synapps.js has no flatpickr import to anchor on', function () {
    $jsDir = base_path('synapps/resources/js');
    File::put($jsDir.'/synapps.js', "Alpine.plugin(mask);\n");

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get($jsDir.'/synapps.js'))->toStartWith("import \"./synapse-components.js\";\n");
});

test('leaves an existing synapse-components.js and import alone', function () {
    $jsDir = base_path('synapps/resources/js');
    File::put($jsDir.'/synapse-components.js', "/* customised */\n");
    File::put($jsDir.'/synapps.js', "import './synapse-components.js';\n");

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get($jsDir.'/synapse-components.js'))->toBe("/* customised */\n")
        ->and(File::get($jsDir.'/synapps.js'))->toBe("import './synapse-components.js';\n");
});

// ─── createBaseJs / rewriteAppJsToFocused ─────────────────────────────────────

test('creates base.js from monolith app.js and rewrites app.js', function () {
    File::put(resource_path('js/app.js'), $this->monolithJs);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::exists(resource_path('js/base.js')))->toBeTrue();

    $baseJs = File::get(resource_path('js/base.js'));
    expect($baseJs)->toContain("import '../../synapps/resources/js/synapps-base.js';")
        ->and($baseJs)->toContain('withBack')        // global helpers preserved
        ->and($baseJs)->not->toContain('theme.js');  // direct theme.js removed

    $appJs = File::get(resource_path('js/app.js'));
    expect($appJs)->toBe("import '../../synapps/resources/js/synapps-app.js';\n");
});

test('creates frontend.js and guest.js layout entry files', function () {
    File::put(resource_path('js/app.js'), $this->monolithJs);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get(resource_path('js/frontend.js')))
        ->toBe("import '../../synapps/resources/js/synapps-frontend.js';\n");

    expect(File::get(resource_path('js/guest.js')))
        ->toBe("import '../../synapps/resources/js/synapps-guest.js';\n");
});

// ─── patchThemeImportLines ────────────────────────────────────────────────────

test('patchThemeImportLines updates synapps-base.css to active theme', function () {
    // Create synapps-base.css with old theme
    $oldContent = "@import './synapps.css';\n@import '../themes/old-theme/css/theme-base.css';\n";
    File::put(base_path('synapps/resources/css/synapps-base.css'), $oldContent);

    config(['synapps.apps.themes.active' => 'new-theme']);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('synapps/resources/css/synapps-base.css'));
    expect($content)->toContain("@import '../themes/new-theme/css/theme-base.css';")
        ->and($content)->not->toContain('old-theme');
});

test('patchThemeImportLines re-points tailwind-ref.css at the active theme', function () {
    $oldContent = "@import \"tailwindcss\";\n@import './synapse-components.css';\n@import '../themes/old-theme/css/theme-base.css';\n";
    File::put(base_path('synapps/resources/css/tailwind-ref.css'), $oldContent);

    config(['synapps.apps.themes.active' => 'new-theme']);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('synapps/resources/css/tailwind-ref.css'));
    expect($content)->toContain("@import '../themes/new-theme/css/theme-base.css';")
        ->and($content)->toContain("@import './synapse-components.css';")
        ->and($content)->not->toContain('old-theme');
});

test('patchThemeImportLines updates synapps-base.js to active theme', function () {
    $oldContent = "import './synapps.js';\nimport '../themes/old-theme/js/theme-base.js';\n";
    File::put(base_path('synapps/resources/js/synapps-base.js'), $oldContent);

    config(['synapps.apps.themes.active' => 'new-theme']);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('synapps/resources/js/synapps-base.js'));
    expect($content)->toContain("import '../themes/new-theme/js/theme-base.js';")
        ->and($content)->not->toContain('old-theme');
});

// ─── expandViteInputs ─────────────────────────────────────────────────────────

test('expandViteInputs adds all 8 entry points to vite.config.js', function () {
    $viteConfig = <<<'JS'
        import laravel from 'laravel-vite-plugin';
        export default {
            plugins: [
                laravel({
                    input: ['resources/css/app.css', 'resources/js/app.js'],
                    refresh: true,
                }),
            ],
        };
        JS;

    File::put(base_path('vite.config.js'), $viteConfig);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('vite.config.js'));
    expect($content)->toContain("'resources/css/base.css'")
        ->and($content)->toContain("'resources/css/app.css'")
        ->and($content)->toContain("'resources/css/frontend.css'")
        ->and($content)->toContain("'resources/css/guest.css'")
        ->and($content)->toContain("'resources/js/base.js'")
        ->and($content)->toContain("'resources/js/app.js'")
        ->and($content)->toContain("'resources/js/frontend.js'")
        ->and($content)->toContain("'resources/js/guest.js'");
});

// ─── Idempotency ──────────────────────────────────────────────────────────────

test('running layouts twice produces identical files', function () {
    File::put(resource_path('css/app.css'), $this->monolithCss);
    File::put(resource_path('js/app.js'), $this->monolithJs);

    $viteConfig = "import laravel from 'laravel-vite-plugin';\nexport default { plugins: [laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true })] };\n";
    File::put(base_path('vite.config.js'), $viteConfig);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    // Capture state after first run
    $firstRun = [
        'synapps-base.css' => File::get(base_path('synapps/resources/css/synapps-base.css')),
        'synapps-app.css' => File::get(base_path('synapps/resources/css/synapps-app.css')),
        'base.css' => File::get(resource_path('css/base.css')),
        'app.css' => File::get(resource_path('css/app.css')),
        'base.js' => File::get(resource_path('js/base.js')),
        'app.js' => File::get(resource_path('js/app.js')),
        'vite.config.js' => File::get(base_path('vite.config.js')),
    ];

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    // All files must be identical after second run
    expect(File::get(base_path('synapps/resources/css/synapps-base.css')))->toBe($firstRun['synapps-base.css'])
        ->and(File::get(base_path('synapps/resources/css/synapps-app.css')))->toBe($firstRun['synapps-app.css'])
        ->and(File::get(resource_path('css/base.css')))->toBe($firstRun['base.css'])
        ->and(File::get(resource_path('css/app.css')))->toBe($firstRun['app.css'])
        ->and(File::get(resource_path('js/base.js')))->toBe($firstRun['base.js'])
        ->and(File::get(resource_path('js/app.js')))->toBe($firstRun['app.js'])
        ->and(File::get(base_path('vite.config.js')))->toBe($firstRun['vite.config.js']);
});

test('vite.config.js not patched twice (no duplicate entries)', function () {
    $viteConfig = "import laravel from 'laravel-vite-plugin';\nexport default { plugins: [laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true })] };\n";
    File::put(base_path('vite.config.js'), $viteConfig);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    $content = File::get(base_path('vite.config.js'));

    // Ensure base.css appears exactly once
    expect(substr_count($content, "'resources/css/base.css'"))->toBe(1);
});

test('app.css not rewritten if already in focused form', function () {
    $focusedCss = "@import '../../synapps/resources/css/synapps-app.css';\n";
    File::put(resource_path('css/app.css'), $focusedCss);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get(resource_path('css/app.css')))->toBe($focusedCss);
});

test('app.js not rewritten if already in focused form', function () {
    $focusedJs = "import '../../synapps/resources/js/synapps-app.js';\n";
    File::put(resource_path('js/app.js'), $focusedJs);

    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get(resource_path('js/app.js')))->toBe($focusedJs);
});
