<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;

class SynAppsPatchResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:patch
        {object?* : Resource(s) to patch — theme, seeder, css, npm, js, vite, layouts, tests. Omit to patch everything.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integrate Synapse resources into app.js, app.css, vite.config.js (with watch ignore), configure testing infrastructure, and clean default Laravel files';

    /**
     * Module/package test paths added to tests/Pest.php's ->in() chain.
     * Vendor package globs (synmod-*, synmodule-*, synapps-*) are filtered by existingTestPaths().
     *
     * @var array<int, string>
     */
    private array $testPathsToAdd = [
        'Unit', // Add Unit directory for unit tests
        '../vendor/vm-engine/synapse/tests/Feature', // Synapse Feature tests
        '../synapps/modules/*/tests/Feature',
        '../vendor/*/synmod-*/tests/Feature',
        '../vendor/*/synmodule-*/tests/Feature',
        '../vendor/*/synapps-*/tests/Feature',
        '../vendor/vm-engine/synapse/tests/Unit', // Synapse Unit tests
        '../synapps/modules/*/tests/Unit',
        '../vendor/*/synmod-*/tests/Unit',
        '../vendor/*/synmodule-*/tests/Unit',
        '../vendor/*/synapps-*/tests/Unit',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        /** @var array<int, string> $objects */
        $objects = $this->argument('object');
        $runAll = $objects === [];
        $wants = fn (string $name): bool => $runAll || in_array($name, $objects, true);

        // Always patch synapps JS/CSS imports (unless a targeted object is given)
        $patches = $runAll ? [
            'app.js — synapps import' => $this->patchAppJsImport(),
            'app.css — synapps import' => $this->patchAppCssImport(),
        ] : [];

        if ($wants('css')) {
            $patches['app.css — @source directives'] = $this->patchCssSource();
        }

        if ($wants('theme')) {
            $patches['app.css / app.js — theme assets'] = $this->patchThemeAssets();
        }

        if ($wants('vite')) {
            $patches['vite.config.js'] = $this->patchViteConfig();
        }

        if ($wants('seeder')) {
            $patches['routes/web.php'] = $this->patchWebRoutes();
            $patches['database/seeders/DatabaseSeeder.php'] = $this->patchDatabaseSeeder();
        }

        if ($wants('npm')) {
            $patches['package.json — watch script'] = $this->patchPackageJson();
        }

        if ($wants('js')) {
            $patches['app.js — global helpers'] = $this->patchAppJsGlobals();
        }

        if ($wants('layouts')) {
            $patches = array_merge($patches, $this->patchSplitLayouts());
        }

        if ($wants('tests')) {
            $patches = array_merge($patches, $this->patchTests());
        }

        foreach ($patches as $label => $patched) {
            if ($patched) {
                $this->components->info("Patched: {$label}");
            }
        }
    }

    // ─── Per-layout CSS/JS split ──────────────────────────────────────────────

    /**
     * Orchestrate all per-layout split operations.
     * Returns a map of label => bool (true = patched).
     *
     * @return array<string, bool>
     */
    protected function patchSplitLayouts(): array
    {
        return [
            'synapps/resources/css — synapps-{base,app,frontend,guest}.css' => $this->createSynappsIntermediaryCss(),
            'synapps/resources/js  — synapps-{base,app,frontend,guest}.js' => $this->createSynappsIntermediaryJs(),
            'synapps/resources/css/tailwind-ref.css' => $this->createTailwindRefCss(),
            'synapps/resources/css — lta.css → synapse-components.css migration' => $this->migrateLtaCssToComponentsCss(),
            'synapps/resources/js — synapse-components.js' => $this->installComponentsJs(),
            'resources/css/base.css' => $this->createBaseCss(),
            'resources/css/frontend.css' => $this->createFrontendCss(),
            'resources/css/guest.css' => $this->createGuestCss(),
            'resources/css/app.css — rewrite to focused import' => $this->rewriteAppCssToFocused(),
            'resources/js/base.js' => $this->createBaseJs(),
            'resources/js/frontend.js' => $this->createFrontendJs(),
            'resources/js/guest.js' => $this->createGuestJs(),
            'resources/js/app.js — rewrite to focused import' => $this->rewriteAppJsToFocused(),
            'synapps-*.css / synapps-*.js — theme import lines' => $this->patchThemeImportLines(),
            'vite.config.js — expand input array' => $this->expandViteInputs(),
            'synapps/resources/views — nav badge line' => $this->patchNavBadgeViews(),
        ];
    }

    /**
     * Add <x-synapse-nav-badge> to the host's menu-item / submenu-item views, right after the
     * item name (idempotent). A view customised beyond recognition is reported, not touched.
     */
    protected function patchNavBadgeViews(): bool
    {
        $tag = '<x-synapse-nav-badge :item="$item" />';
        $name = '\{\{\s*synav\(\)->translate\(\$item\[\'name\'\]\)\s*\}\}';
        $patched = false;

        foreach (['menu-item.blade.php', 'submenu-item.blade.php'] as $file) {
            $path = synapps_path('resources/views/components/layouts/nav/'.$file);
            if (! file_exists($path)) {
                continue;
            }

            $view = (string) file_get_contents($path);
            if (str_contains($view, '<x-synapse-nav-badge')) {
                continue;
            }

            // The name on its own line, optionally closed by the </span> that wraps it (menu-item).
            $pattern = '/^([ \t]*)'.$name.'[ \t]*\n(?:([ \t]*)<\/span>[ \t]*\n)?/m';
            if (preg_match($pattern, $view, $match, PREG_OFFSET_CAPTURE) !== 1) {
                $this->components->warn("Not patched: {$file} is customised — add {$tag} after the item name by hand.");

                continue;
            }

            $wrapped = isset($match[2]) && $match[2][1] !== -1;
            $indent = $wrapped ? $match[2][0] : $match[1][0];
            $insert = ($wrapped ? "\n" : '').$indent.$tag."\n";
            $offset = $match[0][1] + strlen($match[0][0]);

            file_put_contents($path, substr($view, 0, $offset).$insert.substr($view, $offset));
            $patched = true;
        }

        return $patched;
    }

    /**
     * Create synapps/resources/css/synapps-base.css, synapps-app.css,
     * synapps-frontend.css, synapps-guest.css (all idempotent).
     */
    protected function createSynappsIntermediaryCss(): bool
    {
        $cssDir = synapps_path('resources/css');
        $activeTheme = config('synapps.apps.themes.active', 'default');
        $patched = false;

        $files = [
            'synapps-base.css' => implode("\n", [
                "@import './synapps.css';",
                "@import '../themes/{$activeTheme}/css/theme-base.css';",
            ]),
            'synapps-app.css' => "@import '../themes/{$activeTheme}/css/theme-app.css';",
            'synapps-frontend.css' => "@import '../themes/{$activeTheme}/css/theme-frontend.css';",
            'synapps-guest.css' => "@import '../themes/{$activeTheme}/css/theme-guest.css';",
        ];

        foreach ($files as $filename => $content) {
            $path = $cssDir.'/'.$filename;
            if (! file_exists($path)) {
                file_put_contents($path, $content."\n");
                $patched = true;
            }
        }

        return $patched;
    }

    /**
     * Create synapps/resources/css/tailwind-ref.css — a minimal Tailwind v4
     * reference file (no @source directives) that layout-specific CSS entry
     * points (frontend.css, guest.css, app.css) can `@reference` so their
     * `@apply` rules resolve theme tokens without re-scanning blade/js files.
     * Skips if the file already exists.
     */
    protected function createTailwindRefCss(): bool
    {
        $cssDir = synapps_path('resources/css');
        $activeTheme = config('synapps.apps.themes.active', 'default');
        $path = $cssDir.'/tailwind-ref.css';

        if (file_exists($path)) {
            return false;
        }

        $content = implode("\n", [
            '/**',
            ' * Minimal Tailwind v4 reference file — used by layout-specific CSS entry points',
            " * (resources/css/app.css, etc.) that need @apply but aren't the main CSS entry point.",
            ' *',
            ' * Contains: @import "tailwindcss" + all custom @theme tokens (synapse-components + theme-base).',
            ' * NO @source directives — avoids re-scanning all blade/js files.',
            ' *',
            ' * Usage: @reference "../../synapps/resources/css/tailwind-ref.css";',
            ' */',
            '@import "tailwindcss";',
            "@import './synapse-components.css';",
            "@import '../themes/{$activeTheme}/css/theme-base.css';",
        ]);

        file_put_contents($path, $content."\n");

        return true;
    }

    /**
     * Create synapps/resources/js/synapps-base.js, synapps-app.js,
     * synapps-frontend.js, synapps-guest.js (all idempotent).
     */
    protected function createSynappsIntermediaryJs(): bool
    {
        $jsDir = synapps_path('resources/js');
        $activeTheme = config('synapps.apps.themes.active', 'default');
        $patched = false;

        $files = [
            'synapps-base.js' => implode("\n", [
                "import './synapps.js';",
                "import '../themes/{$activeTheme}/js/theme-base.js';",
            ]),
            'synapps-app.js' => "import '../themes/{$activeTheme}/js/theme-app.js';",
            'synapps-frontend.js' => "import '../themes/{$activeTheme}/js/theme-frontend.js';",
            'synapps-guest.js' => "import '../themes/{$activeTheme}/js/theme-guest.js';",
        ];

        foreach ($files as $filename => $content) {
            $path = $jsDir.'/'.$filename;
            if (! file_exists($path)) {
                file_put_contents($path, $content."\n");
                $patched = true;
            }
        }

        return $patched;
    }

    /**
     * Create resources/css/base.css by migrating the monolith app.css content.
     * Replaces the synapps.css import → synapps-base.css and strips the direct
     * theme.css import (now handled by synapps-base.css).
     * Skips if base.css already exists.
     */
    protected function createBaseCss(): bool
    {
        $basePath = resource_path('css/base.css');

        if (file_exists($basePath)) {
            return false;
        }

        $appCssPath = resource_path('css/app.css');

        if (! file_exists($appCssPath)) {
            return false;
        }

        $content = $this->readFile($appCssPath);

        // Replace direct synapps.css import → synapps-base.css
        $content = str_replace(
            "@import '../../synapps/resources/css/synapps.css';",
            "@import '../../synapps/resources/css/synapps-base.css';",
            $content
        );

        // Remove direct theme.css import lines (theme-base.css is now loaded via synapps-base.css)
        $content = preg_replace(
            "/@import\s+['\"]\.\.\/\.\.\/synapps\/resources\/themes\/.*?\/css\/theme\.css['\"]\s*;?\n?/",
            '',
            $content
        );

        file_put_contents($basePath, $content);

        return true;
    }

    /**
     * Create resources/css/frontend.css if it does not exist.
     */
    protected function createFrontendCss(): bool
    {
        $path = resource_path('css/frontend.css');

        if (file_exists($path)) {
            return false;
        }

        file_put_contents($path, "@reference '../../synapps/resources/css/tailwind-ref.css';\n@import '../../synapps/resources/css/synapps-frontend.css';\n");

        return true;
    }

    /**
     * Create resources/css/guest.css if it does not exist.
     */
    protected function createGuestCss(): bool
    {
        $path = resource_path('css/guest.css');

        if (file_exists($path)) {
            return false;
        }

        file_put_contents($path, "@reference '../../synapps/resources/css/tailwind-ref.css';\n@import '../../synapps/resources/css/synapps-guest.css';\n");

        return true;
    }

    /**
     * Rewrite resources/css/app.css from the monolith form to a focused single-import file.
     * Detection: rewrite whenever the direct synapps.css import (monolith marker) is present —
     * mirrors rewriteAppJsToFocused()'s detection, since patchAppCssImport() can re-add that
     * direct import on a later run even after the file was already split.
     */
    protected function rewriteAppCssToFocused(): bool
    {
        $cssPath = resource_path('css/app.css');

        if (! file_exists($cssPath)) {
            return false;
        }

        $content = $this->readFile($cssPath);

        // Check for the direct synapps.css import (monolith marker)
        if (strpos($content, "synapps/resources/css/synapps.css'") === false
            && strpos($content, 'synapps/resources/css/synapps.css"') === false) {
            return false;
        }

        $this->createBackup($cssPath);
        file_put_contents($cssPath, "@reference '../../synapps/resources/css/tailwind-ref.css';\n@import '../../synapps/resources/css/synapps-app.css';\n");

        return true;
    }

    /**
     * Create resources/js/base.js by migrating the monolith app.js content.
     * Replaces the synapps.js import → synapps-base.js and strips the direct
     * theme.js import (now handled by synapps-base.js).
     * Skips if base.js already exists.
     */
    protected function createBaseJs(): bool
    {
        $basePath = resource_path('js/base.js');

        if (file_exists($basePath)) {
            return false;
        }

        $appJsPath = resource_path('js/app.js');

        if (! file_exists($appJsPath)) {
            return false;
        }

        $content = $this->readFile($appJsPath);

        // Replace direct synapps.js import → synapps-base.js
        $content = str_replace(
            "import '../../synapps/resources/js/synapps.js';",
            "import '../../synapps/resources/js/synapps-base.js';",
            $content
        );

        // Remove direct theme.js import lines (theme-base.js is now loaded via synapps-base.js)
        $content = preg_replace(
            "/import\s+['\"]\.\.\/\.\.\/synapps\/resources\/themes\/.*?\/js\/theme\.js['\"]\s*;?\n?/",
            '',
            $content
        );

        file_put_contents($basePath, $content);

        return true;
    }

    /**
     * Create resources/js/frontend.js if it does not exist.
     */
    protected function createFrontendJs(): bool
    {
        $path = resource_path('js/frontend.js');

        if (file_exists($path)) {
            return false;
        }

        file_put_contents($path, "import '../../synapps/resources/js/synapps-frontend.js';\n");

        return true;
    }

    /**
     * Create resources/js/guest.js if it does not exist.
     */
    protected function createGuestJs(): bool
    {
        $path = resource_path('js/guest.js');

        if (file_exists($path)) {
            return false;
        }

        file_put_contents($path, "import '../../synapps/resources/js/synapps-guest.js';\n");

        return true;
    }

    /**
     * Rewrite resources/js/app.js from the monolith form to a focused single-import file.
     * Detection: if `synapps/resources/js/synapps.js` (direct) is imported, it's still monolith.
     */
    protected function rewriteAppJsToFocused(): bool
    {
        $jsPath = resource_path('js/app.js');

        if (! file_exists($jsPath)) {
            return false;
        }

        $content = $this->readFile($jsPath);

        // Check for the direct synapps.js import (monolith marker)
        if (strpos($content, "synapps/resources/js/synapps.js'") === false
            && strpos($content, 'synapps/resources/js/synapps.js"') === false) {
            return false;
        }

        $this->createBackup($jsPath);
        file_put_contents($jsPath, "import '../../synapps/resources/js/synapps-app.js';\n");

        return true;
    }

    /**
     * Patch the active-theme import lines in all 8 synapps-*.css / synapps-*.js files
     * and in tailwind-ref.css (the @reference target for @apply in layout CSS entries).
     * Updates the theme folder name to match the currently active theme.
     * Idempotent — only writes when the theme path has changed.
     */
    protected function patchThemeImportLines(): bool
    {
        $activeTheme = config('synapps.apps.themes.active', 'default');
        $patched = false;

        $cssFiles = [
            'synapps-base.css' => "../themes/{$activeTheme}/css/theme-base.css",
            'synapps-app.css' => "../themes/{$activeTheme}/css/theme-app.css",
            'synapps-frontend.css' => "../themes/{$activeTheme}/css/theme-frontend.css",
            'synapps-guest.css' => "../themes/{$activeTheme}/css/theme-guest.css",
            'tailwind-ref.css' => "../themes/{$activeTheme}/css/theme-base.css",
        ];

        $cssDir = synapps_path('resources/css');
        foreach ($cssFiles as $filename => $themePath) {
            $filePath = $cssDir.'/'.$filename;

            if (! file_exists($filePath)) {
                continue;
            }

            $content = $this->readFile($filePath);
            $updated = preg_replace(
                "/@import\s+['\"]\.\.\/themes\/[^'\"]+\/css\/theme-[a-z][a-z0-9-]*\.css['\"]\s*;/",
                "@import '{$themePath}';",
                $content
            );

            if ($updated !== $content) {
                file_put_contents($filePath, $updated);
                $patched = true;
            }
        }

        $jsFiles = [
            'synapps-base.js' => "../themes/{$activeTheme}/js/theme-base.js",
            'synapps-app.js' => "../themes/{$activeTheme}/js/theme-app.js",
            'synapps-frontend.js' => "../themes/{$activeTheme}/js/theme-frontend.js",
            'synapps-guest.js' => "../themes/{$activeTheme}/js/theme-guest.js",
        ];

        $jsDir = synapps_path('resources/js');
        foreach ($jsFiles as $filename => $themePath) {
            $filePath = $jsDir.'/'.$filename;

            if (! file_exists($filePath)) {
                continue;
            }

            $content = $this->readFile($filePath);
            $updated = preg_replace(
                "/import\s+['\"]\.\.\/themes\/[^'\"]+\/js\/theme-[a-z][a-z0-9-]*\.js['\"]\s*;/",
                "import '{$themePath}';",
                $content
            );

            if ($updated !== $content) {
                file_put_contents($filePath, $updated);
                $patched = true;
            }
        }

        return $patched;
    }

    /**
     * Migrate an already-set-up project off the retired lta.css:
     * 1. Copy synapse-components.css into the project if missing.
     * 2. Rewrite synapps.css / tailwind-ref.css imports from ./lta.css to
     *    ./synapse-components.css.
     * 3. Delete the now-unused synapps/resources/css/lta.css.
     *
     * Idempotent — returns false once the project has already migrated.
     */
    protected function migrateLtaCssToComponentsCss(): bool
    {
        $cssDir = synapps_path('resources/css');
        $ltaPath = $cssDir.'/lta.css';
        $componentsPath = $cssDir.'/synapse-components.css';
        $patched = false;

        if (! file_exists($ltaPath) && file_exists($componentsPath)) {
            return false;
        }

        if (! file_exists($componentsPath)) {
            $stubPath = __DIR__.'/../../../stubs/synapps/resources/css/synapse-components.css';
            file_put_contents($componentsPath, $this->readFile($stubPath));
            $patched = true;
        }

        foreach (['synapps.css', 'tailwind-ref.css'] as $filename) {
            $filePath = $cssDir.'/'.$filename;

            if (! file_exists($filePath)) {
                continue;
            }

            $content = $this->readFile($filePath);
            $updated = preg_replace(
                "/@import\s+['\"]\.\/lta\.css['\"]\s*;/",
                "@import './synapse-components.css';",
                $content
            );

            if ($updated !== $content) {
                file_put_contents($filePath, $updated);
                $patched = true;
            }
        }

        if (file_exists($ltaPath)) {
            unlink($ltaPath);
            $patched = true;
        }

        return $patched;
    }

    /**
     * Install synapse-components.js (the components' Alpine.data registrations,
     * formerly inline <script>s in each view) and import it from synapps.js.
     *
     * Idempotent — an existing synapse-components.js or import is left untouched.
     */
    protected function installComponentsJs(): bool
    {
        $jsDir = synapps_path('resources/js');
        $componentsPath = $jsDir.'/synapse-components.js';
        $synappsJsPath = $jsDir.'/synapps.js';
        $patched = false;

        if (! file_exists($componentsPath)) {
            $stubPath = __DIR__.'/../../../stubs/synapps/resources/js/synapse-components.js';
            file_put_contents($componentsPath, $this->readFile($stubPath));
            $patched = true;
        }

        if (! file_exists($synappsJsPath)) {
            return $patched;
        }

        $content = $this->readFile($synappsJsPath);

        if (str_contains($content, 'synapse-components.js')) {
            return $patched;
        }

        $import = 'import "./synapse-components.js";';
        $anchored = preg_replace('/^(import\s+flatpickr\s+from\s+["\']flatpickr["\'];)$/m', "$1\n{$import}", $content, 1);

        file_put_contents($synappsJsPath, $anchored !== null && $anchored !== $content ? $anchored : $import."\n".$content);

        return true;
    }

    /**
     * Expand the Vite input array in vite.config.js to include the 6 new layout entry points.
     * Idempotent — skips if resources/css/base.css is already listed.
     */
    protected function expandViteInputs(): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! file_exists($viteConfigPath)) {
            return false;
        }

        $content = $this->readFile($viteConfigPath);

        // Already expanded — idempotency check
        if (strpos($content, 'resources/css/base.css') !== false) {
            return false;
        }

        $expandedInput = implode('', [
            "input: [\n",
            "                'resources/css/base.css',\n",
            "                'resources/css/app.css',\n",
            "                'resources/css/frontend.css',\n",
            "                'resources/css/guest.css',\n",
            "                'resources/js/base.js',\n",
            "                'resources/js/app.js',\n",
            "                'resources/js/frontend.js',\n",
            "                'resources/js/guest.js',\n",
            '            ]',
        ]);

        // Replace the existing compact input array
        $updated = preg_replace(
            "/input:\s*\[(?:[^\]]*)\]/s",
            $expandedInput,
            $content,
            1,
            $count
        );

        if ($count === 0 || $updated === $content) {
            return false;
        }

        $this->createBackup($viteConfigPath);
        file_put_contents($viteConfigPath, $updated);

        return true;
    }

    /**
     * Patch resources/js/app.js to add synapps.js import.
     */
    protected function patchAppJsImport(): bool
    {
        $jsLine = "import '../../synapps/resources/js/synapps.js';";

        $jsPatched = false;
        $jsLines = file(resource_path('js/app.js')) ?: [];
        $lastImportJs = 0;
        foreach ($jsLines as $row => $line) {
            if (strpos($line, $jsLine) !== false) {
                $jsPatched = false;
                break;
            }
            if (strpos($line, 'import') !== false) {
                $lastImportJs = $row;
            }
            $jsPatched = true;
        }

        if ($jsPatched || empty($jsLines)) {
            array_splice($jsLines, $lastImportJs + 1, 0, $jsLine."\n");
            $this->createBackup(resource_path('js/app.js'));
            file_put_contents(resource_path('js/app.js'), implode("\n", $jsLines));

            return true;
        }

        return false;
    }

    /**
     * Patch resources/css/app.css to add synapps.css import.
     */
    protected function patchAppCssImport(): bool
    {
        $cssLine = "@import '../../synapps/resources/css/synapps.css';";

        $cssPatched = false;
        $cssLines = file(resource_path('css/app.css')) ?: [];
        $lastImportCss = 0;
        foreach ($cssLines as $row => $line) {
            if (strpos($line, $cssLine) !== false) {
                $cssPatched = false;
                break;
            }
            if (strpos($line, '@import') !== false) {
                $lastImportCss = $row;
            }
            $cssPatched = true;
        }

        if ($cssPatched || empty($cssLines)) {
            array_splice($cssLines, $lastImportCss + 1, 0, $cssLine."\n");
            $this->createBackup(resource_path('css/app.css'));
            file_put_contents(resource_path('css/app.css'), implode("\n", $cssLines));

            return true;
        }

        return false;
    }

    /**
     * Patch app.css to add @source directives for module files
     */
    protected function patchCssSource(): bool
    {
        $cssPath = resource_path('css/app.css');

        if (! file_exists($cssPath)) {
            return false;
        }

        $content = $this->readFile($cssPath);

        // Define @source directives to add
        $sourceDirectives = [
            "@source '../../synapps/**/*.blade.php';",
            "@source '../../synapps/**/*.js';",
            "@source '../../synapps/resources/themes/**/*.blade.php';",
            "@source '../../synapps/resources/themes/**/*.js';",
            "@source '../../vendor/vm-engine/*/**/*.blade.php';",
            "@source '../../vendor/vm-engine/*/**/*.js';",
            "@source '../../vendor/*/synmod-*/**/*.blade.php';",
            "@source '../../vendor/*/synmod-*/**/*.js';",
            "@source '../../vendor/*/synapps-*/**/*.blade.php';",
            "@source '../../vendor/*/synapps-*/**/*.js';",
        ];

        $lines = explode("\n", $content);
        $lastSourceIndex = -1;
        $anyPatched = false;

        // Find the last @source line
        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*@source\s+/', trim($line))) {
                $lastSourceIndex = $index;
            }
        }

        // If no @source found, find @import "tailwindcss" to add after it
        if ($lastSourceIndex === -1) {
            foreach ($lines as $index => $line) {
                if (preg_match('/^\s*@import\s+["\']tailwindcss["\']/', trim($line))) {
                    $lastSourceIndex = $index;
                    break;
                }
            }
        }

        // Add missing @source directives
        foreach ($sourceDirectives as $directive) {
            if (strpos($content, $directive) === false) {
                $lastSourceIndex++;
                array_splice($lines, $lastSourceIndex, 0, $directive);
                $anyPatched = true;
            }
        }

        if ($anyPatched) {
            $this->createBackup($cssPath);
            file_put_contents($cssPath, implode("\n", $lines));
        }

        return $anyPatched;
    }

    /**
     * Patch app.css and app.js to include active theme files
     */
    protected function patchThemeAssets(): bool
    {
        // Get active theme from config (fallback to default if config not loaded)
        $activeTheme = config('synapps.apps.themes.active', 'default');
        $themePath = synapps_path("resources/themes/{$activeTheme}");

        if (! is_dir($themePath)) {
            return false;
        }

        $patched = false;

        // Patch CSS
        if (file_exists($themePath.'/css/theme.css')) {
            $cssPath = resource_path('css/app.css');
            $cssLine = "@import '../../synapps/resources/themes/{$activeTheme}/css/theme.css';";

            $content = $this->readFile($cssPath);

            // Remove old theme imports
            $content = preg_replace(
                "/@import\s+['\"]\.\.\/\.\.\/synapps\/resources\/themes\/.*?\/css\/theme\.css['\"]\s*;?\n?/",
                '',
                $content
            );

            // Add new theme import after synapps.css import
            if (strpos($content, $cssLine) === false) {
                $content = preg_replace(
                    "/(@import\s+['\"]\.\.\/\.\.\/synapps\/resources\/css\/synapps\.css['\"]\s*;)/",
                    "$1\n{$cssLine}",
                    $content,
                    1,
                    $count
                );

                if ($count > 0) {
                    $this->createBackup($cssPath);
                    file_put_contents($cssPath, $content);
                    $patched = true;
                }
            }
        }

        // Patch JS
        if (file_exists($themePath.'/js/theme.js')) {
            $jsPath = resource_path('js/app.js');
            $jsLine = "import '../../synapps/resources/themes/{$activeTheme}/js/theme.js';";

            $content = $this->readFile($jsPath);

            // Remove old theme imports
            $content = preg_replace(
                "/import\s+['\"]\.\.\/\.\.\/synapps\/resources\/themes\/.*?\/js\/theme\.js['\"]\s*;?\n?/",
                '',
                $content
            );

            // Add new theme import after synapps.js import
            if (strpos($content, $jsLine) === false) {
                $content = preg_replace(
                    "/(import\s+['\"]\.\.\/\.\.\/synapps\/resources\/js\/synapps\.js['\"]\s*;)/",
                    "$1\n{$jsLine}",
                    $content,
                    1,
                    $count
                );

                if ($count > 0) {
                    $this->createBackup($jsPath);
                    file_put_contents($jsPath, $content);
                    $patched = true;
                }
            }
        }

        return $patched;
    }

    /**
     * Patch vite.config.js to add static asset copying with watch mode optimization
     */
    protected function patchViteConfig(): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! file_exists($viteConfigPath)) {
            return false;
        }

        $content = $this->readFile($viteConfigPath);

        $patched = false;

        // Step 1: Convert to function-based config if not already (for watch mode detection)
        if (strpos($content, "process.argv.includes('--watch')") === false) {
            // Check if using object-based config
            if (preg_match('/export\s+default\s+defineConfig\s*\(\s*\{/', $content)) {
                // Convert to function-based config
                $content = preg_replace(
                    '/export\s+default\s+defineConfig\s*\(\s*\{/',
                    "export default defineConfig(({ command, mode }) => {\n    const isWatch = process.argv.includes('--watch');\n\n    return {",
                    $content,
                    1,
                    $count
                );

                if ($count > 0) {
                    // Update closing parenthesis
                    $content = preg_replace('/\}\);(\s*)$/', '}});$1', $content, 1);
                    $patched = true;
                }
            }
        }

        // Step 2: Check if vite-plugin-static-copy needs to be added
        if (strpos($content, 'vite-plugin-static-copy') === false) {
            // Add imports after existing imports
            $importLines = [
                "import { viteStaticCopy } from 'vite-plugin-static-copy';",
                "import { glob } from 'glob';",
            ];

            // Find the last import line
            $lines = explode("\n", $content);
            $lastImportIndex = 0;
            foreach ($lines as $index => $line) {
                if (preg_match('/^import\s+/', trim($line))) {
                    $lastImportIndex = $index;
                }
            }

            // Insert imports after last import
            array_splice($lines, $lastImportIndex + 1, 0, $importLines);
            $content = implode("\n", $lines);
            $patched = true;
        }

        // Step 3: Add viteStaticCopy plugin with conditional watch mode handling
        if (strpos($content, 'viteStaticCopy({') === false) {
            // Plugin config wrapped in conditional for watch mode
            $pluginConfig = <<<'PLUGIN'
// Only enable static copy in production builds, not in watch mode
        ...(!isWatch ? [
            viteStaticCopy({
                targets: [
                    // Copy from synapps/resources/assets
                    {
                        src: 'synapps/resources/assets/**/*',
                        dest: '../assets',
                    },
                    // Copy theme assets (images, fonts, etc. - NOT css/js, those are compiled)
                    ...glob.sync('synapps/resources/themes/*/assets').map(dir => {
                        const normalizedDir = dir.replace(/\\/g, '/');
                        return {
                            src: `${normalizedDir}/**/*`,
                            dest: `../assets/themes/${normalizedDir.split('/')[3]}`
                        };
                    }),
                    // Copy from vendor/*/synmod-*/resources/assets
                    ...glob.sync('vendor/*/synmod-*/resources/assets').map(dir => {
                        const normalizedDir = dir.replace(/\\/g, '/');
                        return {
                            src: `${normalizedDir}/**/*`,
                            dest: '../assets',
                        };
                    }),
                    // Copy from vendor/vm-engine/synapps-*/resources/assets
                    ...glob.sync('vendor/vm-engine/synapps-*/resources/assets').map(dir => {
                        const normalizedDir = dir.replace(/\\/g, '/');
                        return {
                            src: `${normalizedDir}/**/*`,
                            dest: '../assets',
                        };
                    }),
                ],
            })
        ] : []),
PLUGIN;

            // Find tailwindcss() line and add viteStaticCopy after it
            // Use preg_replace_callback to prevent preg_replace from processing backslashes
            // in $pluginConfig (which contains JS regex literals like /\\/g)
            $count = 0;
            $content = preg_replace_callback(
                '/(tailwindcss\(\),)/',
                static function (array $matches) use ($pluginConfig): string {
                    return $matches[1]."\n".$pluginConfig;
                },
                $content,
                1,
                $count
            );

            if ($count > 0) {
                $patched = true;
            }
        } else {
            // Step 4: viteStaticCopy exists - ensure it's wrapped in watch mode conditional
            if (! str_contains($content, '...(!isWatch ? [') && str_contains($content, 'viteStaticCopy({')) {
                // Wrap existing viteStaticCopy in conditional
                $content = preg_replace(
                    '/(tailwindcss\(\),)\s*\n\s*(viteStaticCopy\(\{)/s',
                    "$1\n        // Only enable static copy in production builds, not in watch mode\n        ...(!isWatch ? [\n            $2",
                    $content,
                    1,
                    $count
                );

                if ($count > 0) {
                    // Find the closing of viteStaticCopy and add the closing bracket
                    $content = preg_replace(
                        '/(viteStaticCopy\(\{.*?\}\)\s*),/s',
                        "$1\n        ] : []),",
                        $content,
                        1
                    );
                    $patched = true;
                }
            }

            // Step 5: Ensure theme assets line exists
            if (strpos($content, 'synapps/resources/themes/*/assets') === false) {
                // Find the targets array and add theme assets line
                $themeAssetsLine = <<<'THEME'
                    // Copy theme assets (images, fonts, etc. - NOT css/js, those are compiled)
                    ...glob.sync('synapps/resources/themes/*/assets').map(dir => {
                        const normalizedDir = dir.replace(/\\/g, '/');
                        return {
                            src: `${normalizedDir}/**/*`,
                            dest: `../assets/themes/${normalizedDir.split('/')[3]}`
                        };
                    }),
THEME;

                // Try to add after the synapps/resources/assets entry
                $pattern = '/(src:\s*[\'"]synapps\/resources\/assets\/\*\*\/\*[\'"],?\s*dest:\s*[\'"]\.\.\/assets[\'"].*?\},?)/s';
                $replacement = "$1\n".$themeAssetsLine;

                $content = preg_replace(
                    $pattern,
                    $replacement,
                    $content,
                    1,
                    $count
                );

                if ($count > 0) {
                    $patched = true;
                }
            }
        }

        // Step 6: Ensure server.watch config exists with proper ignored paths
        if (strpos($content, 'server:') === false) {
            // Add complete server.watch config
            $serverConfig = <<<'SERVER'
    server: {
        cors: true,
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: [
                "**/storage/framework/views/**",
                "**/public/build/**",
                "**/public/assets/**",
                "**/vendor/**",
                "**/node_modules/**",
                "**/.git/**",
            ],
        },
    },
SERVER;

            // Add before the closing brace of the config
            $content = preg_replace(
                '/(\n\s*}\s*\}\);?\s*)$/s',
                "\n$serverConfig\$1",
                $content,
                1,
                $count
            );

            if ($count > 0) {
                $patched = true;
            }
        } else {
            // Server config exists, ensure watch.ignored has all necessary paths
            $requiredPaths = [
                '**/public/build/**',
                '**/public/assets/**',
                '**/vendor/**',
                '**/node_modules/**',
                '**/.git/**',
            ];

            foreach ($requiredPaths as $path) {
                $escapedPath = preg_quote($path, '/');
                if (! preg_match("/$escapedPath/", $content)) {
                    // Try to add to existing ignored array
                    $content = preg_replace(
                        "/(ignored:\s*\[)/",
                        "$1\n                \"$path\",",
                        $content,
                        1,
                        $count
                    );

                    if ($count > 0) {
                        $patched = true;
                    }
                }
            }

            // Ensure usePolling and interval exist for Windows compatibility
            if (strpos($content, 'usePolling') === false) {
                $content = preg_replace(
                    '/(watch:\s*\{)/',
                    "$1\n            usePolling: true,\n            interval: 1000,",
                    $content,
                    1,
                    $count
                );

                if ($count > 0) {
                    $patched = true;
                }
            }
        }

        if ($patched) {
            $this->createBackup($viteConfigPath);
            file_put_contents($viteConfigPath, $content);
        }

        return $patched;
    }

    /**
     * Patch routes/web.php to disable the default Laravel welcome route.
     * Only comments out the default route if no other routes are defined.
     *
     * @return bool True if the file was patched, false otherwise.
     */
    protected function patchWebRoutes(): bool
    {
        $webRoutesPath = base_path('routes/web.php');

        if (! file_exists($webRoutesPath)) {
            return false;
        }

        $content = $this->readFile($webRoutesPath);
        $lines = file($webRoutesPath, FILE_IGNORE_NEW_LINES) ?: [];

        // Check if default route exists and is not already commented.
        // Matches both the closure-based skeleton (`Route::get('/', function () {...})`)
        // and the `Route::view('/', 'welcome')` form used by newer Laravel skeletons.
        $hasDefaultRoute = false;
        $defaultRoutePattern = "/Route::(get\s*\(\s*['\"]\/['\"]\s*,\s*function\s*\(\s*\)|view\s*\(\s*['\"]\/['\"]\s*,)/";

        foreach ($lines as $line) {
            if (preg_match($defaultRoutePattern, $line) && ! $this->isCommented($line)) {
                $hasDefaultRoute = true;
                break;
            }
        }

        if (! $hasDefaultRoute) {
            return false;
        }

        // Check if there are other route definitions (excluding the default route)
        $hasOtherRoutes = $this->hasOtherRouteDefinitions($lines, $defaultRoutePattern);

        if ($hasOtherRoutes) {
            // Skip patching if other routes exist
            return false;
        }

        // Comment out the default route (multi-line)
        $patched = $this->commentOutDefaultRoute($lines, $defaultRoutePattern);

        if ($patched) {
            $this->createBackup($webRoutesPath);
            file_put_contents($webRoutesPath, implode("\n", $lines)."\n");

            return true;
        }

        return false;
    }

    /**
     * Check if a line is already commented.
     */
    protected function isCommented(string $line): bool
    {
        return preg_match('/^\s*\/\//', trim($line)) || preg_match('/^\s*\/\*/', trim($line));
    }

    /**
     * Check if there are other route definitions beyond the default welcome route.
     *
     * @param  array<int, string>  $lines
     */
    protected function hasOtherRouteDefinitions(array $lines, string $defaultRoutePattern): bool
    {
        $routePatterns = [
            '/Route::(get|post|put|patch|delete|options|any|match|redirect|view|permanentRedirect|resource|apiResource|group)\s*\(/',
        ];

        $inDefaultRoute = false;
        $braceCount = 0;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Track if we're inside the default route closure
            if (preg_match($defaultRoutePattern, $line)) {
                $inDefaultRoute = true;
            }

            if ($inDefaultRoute) {
                $braceCount += substr_count($line, '{') - substr_count($line, '}');
                if ($braceCount <= 0) {
                    $inDefaultRoute = false;
                    $braceCount = 0;
                }

                continue;
            }

            // Check for other route definitions (not commented)
            if (! $this->isCommented($trimmedLine)) {
                foreach ($routePatterns as $pattern) {
                    if (preg_match($pattern, $trimmedLine)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Comment out the default Laravel route (handles multi-line).
     *
     * @param  array<int, string>  $lines
     */
    protected function commentOutDefaultRoute(array &$lines, string $defaultRoutePattern): bool
    {
        $patched = false;
        $inDefaultRoute = false;
        $braceCount = 0;
        $startLine = -1;

        foreach ($lines as $index => $line) {
            // Find the start of the default route
            if (! $inDefaultRoute && preg_match($defaultRoutePattern, $line)) {
                $inDefaultRoute = true;
                $startLine = $index;
            }

            if ($inDefaultRoute) {
                // Count braces to track closure
                $braceCount += substr_count($line, '{') - substr_count($line, '}');

                // Comment out this line
                if (! $this->isCommented($line)) {
                    $lines[$index] = '// '.$line;
                    $patched = true;
                }

                // Check if we've closed the route definition
                if ($braceCount <= 0) {
                    $inDefaultRoute = false;
                    $braceCount = 0;
                    break;
                }
            }
        }

        return $patched;
    }

    /**
     * Patch database/seeders/DatabaseSeeder.php to comment out default Laravel user seeder.
     * Comments out User::factory() calls if they exist.
     *
     * @return bool True if the file was patched, false otherwise.
     */
    protected function patchDatabaseSeeder(): bool
    {
        $seederPath = base_path('database/seeders/DatabaseSeeder.php');

        if (! file_exists($seederPath)) {
            return false;
        }

        $content = $this->readFile($seederPath);
        $lines = file($seederPath, FILE_IGNORE_NEW_LINES) ?: [];

        $patched = false;

        // Patterns to match User::factory() calls
        $userFactoryPatterns = [
            '/^\s*User::factory\s*\(/',  // Matches: User::factory(
            '/^\s*\\\\App\\\\Models\\\\User::factory\s*\(/',  // Matches: \App\Models\User::factory(
        ];

        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);

            // Skip if already commented
            if ($this->isCommented($line)) {
                continue;
            }

            // Check if line matches any User::factory() pattern
            foreach ($userFactoryPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    // Comment out this line
                    $lines[$index] = '        // '.$trimmedLine;
                    $patched = true;

                    // Check if this is a multi-line factory call
                    // Look for lines that are part of the same statement (no semicolon yet)
                    $checkIndex = $index + 1;
                    while (isset($lines[$checkIndex])) {
                        $nextLine = $lines[$checkIndex];
                        $nextTrimmed = trim($nextLine);

                        // If already commented, skip
                        if ($this->isCommented($nextLine)) {
                            $checkIndex++;

                            continue;
                        }

                        // If empty line, stop
                        if (empty($nextTrimmed)) {
                            break;
                        }

                        // Comment out continuation lines until we find semicolon
                        $lines[$checkIndex] = '        // '.$nextTrimmed;

                        // Stop if we found the semicolon (end of statement)
                        if (str_contains($nextTrimmed, ';')) {
                            break;
                        }

                        $checkIndex++;
                    }

                    break;
                }
            }
        }

        if ($patched) {
            $this->createBackup($seederPath);
            file_put_contents($seederPath, implode("\n", $lines)."\n");
        }

        return $patched;
    }

    /**
     * Read a file's contents, throwing if the read fails.
     * Callers already check file_exists() before calling this — a false
     * result here means a genuine I/O failure (permissions, race), not a
     * missing file.
     */
    protected function readFile(string $path): string
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Unable to read file: {$path}");
        }

        return $contents;
    }

    /**
     * Create a backup of a file before patching.
     * Only creates the backup once — skips if a .bak already exists.
     */
    protected function createBackup(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        $backupPath = $path.'.bak';

        if (! file_exists($backupPath)) {
            copy($path, $backupPath);
        }
    }

    /**
     * Patch app.js to add global Synapse JS helper functions (e.g. withBack).
     * Idempotent — skips if the marker comment already exists.
     */
    protected function patchAppJsGlobals(): bool
    {
        $jsPath = resource_path('js/app.js');

        if (! file_exists($jsPath)) {
            return false;
        }

        $marker = '// Synapse global helpers';

        $content = $this->readFile($jsPath);

        if (str_contains($content, $marker)) {
            return false;
        }

        $snippet = <<<'JS'

// Synapse global helpers
window.withBack = (url) => {
    const separator = url.includes('?') ? '&' : '?';
    return url + separator + 'back=' + encodeURIComponent(window.location.href);
};

// Expose withBack as an Alpine magic ($withBack) so CSP-safe Alpine
// expressions can call it — Alpine's CSP evaluator only resolves its own
// reactive scope + registered magics, not arbitrary window globals.
// Registered inside alpine:init so it works regardless of script load order.
document.addEventListener('alpine:init', () => {
    Alpine.magic('withBack', () => window.withBack);
});

// Re-compute the back URL at click time.
// Livewire updates the browser URL AFTER DOM morph, so Alpine's :href
// bindings capture a stale URL (e.g. missing ?page=11 after pagination).
// This handler fires before wire:navigate reads the href, ensuring the
// back parameter always reflects the current page state.
document.addEventListener('mousedown', (e) => {
    const link = e.target.closest('a[href*="back="]');
    if (!link) return;
    try {
        const url = new URL(link.href);
        if (url.searchParams.has('back')) {
            url.searchParams.set('back', window.location.href);
            link.href = url.toString();
        }
    } catch { /* ignore invalid URLs */ }
});
JS;

        $this->createBackup($jsPath);
        file_put_contents($jsPath, $content.$snippet);

        return true;
    }

    /**
     * Patch package.json to add 'npm run watch' script.
     * Adds a watch script if it doesn't already exist.
     *
     * @return bool True if the file was patched, false otherwise.
     */
    protected function patchPackageJson(): bool
    {
        $packageJsonPath = base_path('package.json');

        if (! file_exists($packageJsonPath)) {
            return false;
        }

        $packageJson = read_json_file($packageJsonPath);

        // Ensure scripts section exists
        if (! isset($packageJson['scripts'])) {
            $packageJson['scripts'] = [];
        }

        // Check if watch script already exists
        if (
            isset($packageJson['scripts']['watch'])
            && $packageJson['scripts']['watch'] === 'vite build --watch'
        ) {
            return false;
        }

        // Add watch script (compile only, no dev server)
        $packageJson['scripts']['watch'] = 'vite build --watch';

        // Write back to package.json with pretty formatting
        $this->createBackup($packageJsonPath);
        file_put_contents(
            $packageJsonPath,
            json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        return true;
    }

    // ─── Test infrastructure (tests/Pest.php, .env.testing, phpunit.xml) ──────

    /**
     * Configure testing infrastructure: module paths in tests/Pest.php,
     * .env.testing, composer.json's test script, and phpunit.xml.
     *
     * @return array<string, bool>
     */
    protected function patchTests(): array
    {
        return [
            'tests/Pest.php — module paths' => $this->patchPestFile(),
            '.env.testing' => $this->ensureEnvTesting(),
            'composer.json — test script' => $this->ensureComposerTestScript(),
            'phpunit.xml' => $this->patchPhpunitXml(),
        ];
    }

    protected function patchPestFile(): bool
    {
        $pestPath = base_path('tests/Pest.php');

        if (! file_exists($pestPath)) {
            $this->components->error('tests/Pest.php not found.');
            $this->components->info('Please ensure Pest is installed and tests/Pest.php exists.');

            return false;
        }

        $content = $this->readFile($pestPath);

        if ($this->isPestAlreadyPatched($content)) {
            return false;
        }

        $patchedContent = $this->patchPestContent($content);

        if ($patchedContent === null) {
            $this->components->error('Failed to patch tests/Pest.php.');
            $this->components->warn('Could not find the pest()->extend()->in() chain.');
            $this->components->info('Please manually add module test paths to tests/Pest.php');

            return false;
        }

        $this->createBackup($pestPath);
        file_put_contents($pestPath, $patchedContent);

        return true;
    }

    protected function ensureEnvTesting(): bool
    {
        $envTestingPath = base_path('.env.testing');

        /** @var array<string, string> $requiredKeys */
        $requiredKeys = [
            'APP_ENV' => 'testing',
            'APP_LOCALE' => 'en',
            'APP_FALLBACK_LOCALE' => 'en',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DRIVER' => 'array',
            'MAIL_MAILER' => 'array',
            'BCRYPT_ROUNDS' => '4',
        ];

        $appKey = 'base64:'.base64_encode(Encrypter::generateKey((string) config('app.cipher', 'AES-256-CBC')));

        $defaultContent = <<<ENV
APP_ENV=testing
APP_KEY={$appKey}
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

CACHE_STORE=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
MAIL_MAILER=array

BCRYPT_ROUNDS=4

ENV;

        if (! file_exists($envTestingPath)) {
            file_put_contents($envTestingPath, $defaultContent);

            return true;
        }

        $existingContent = $this->readFile($envTestingPath);
        $keyFilled = false;

        if (preg_match('/^APP_KEY=[ \t]*$/m', $existingContent)) {
            $existingContent = (string) preg_replace('/^APP_KEY=[ \t]*$/m', "APP_KEY={$appKey}", $existingContent, 1);
            $keyFilled = true;
        }

        /** @var array<string, string> $missingLines */
        $missingLines = [];
        $requiredKeys['APP_KEY'] = $appKey;

        foreach ($requiredKeys as $key => $value) {
            if (! preg_match('/^'.preg_quote($key, '/').'=/m', $existingContent)) {
                $missingLines[$key] = "{$key}={$value}";
            }
        }

        if (empty($missingLines) && ! $keyFilled) {
            return false;
        }

        $this->createBackup($envTestingPath);
        $appendContent = empty($missingLines) ? '' : "\n".implode("\n", $missingLines)."\n";
        file_put_contents($envTestingPath, rtrim($existingContent)."\n".$appendContent);

        return true;
    }

    protected function ensureComposerTestScript(): bool
    {
        $composerPath = base_path('composer.json');

        if (! file_exists($composerPath)) {
            $this->components->error('composer.json not found.');

            return false;
        }

        $composerContent = $this->readFile($composerPath);
        $composer = json_decode($composerContent, true);

        if (! isset($composer['scripts']['test'])) {
            return false;
        }

        $testScript = $composer['scripts']['test'];

        $hasConfigClear = false;
        $hasConfigCache = false;
        $hasTestEnvFlag = false;

        foreach ($testScript as $command) {
            if (str_contains($command, 'config:clear')) {
                $hasConfigClear = true;
            }
            if (str_contains($command, 'config:cache')) {
                $hasConfigCache = true;
            }
            if (str_contains($command, 'test --env=testing') || str_contains($command, 'test --env testing')) {
                $hasTestEnvFlag = true;
            }
        }

        if ($hasConfigClear && $hasConfigCache && $hasTestEnvFlag) {
            return false;
        }

        $composer['scripts']['test'] = [
            '@php artisan config:clear --ansi',
            '@php artisan config:cache --ansi',
            '@php artisan test --env=testing',
        ];

        $newComposerContent = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->createBackup($composerPath);
        file_put_contents($composerPath, $newComposerContent);

        return true;
    }

    /**
     * Pest test paths to add, dropping vendor package globs (synmod-*, synmodule-*, synapps-*)
     * that match no installed package folder.
     *
     * @return array<int, string>
     */
    protected function existingTestPaths(): array
    {
        return array_values(array_filter(
            $this->testPathsToAdd,
            fn (string $path): bool => ! preg_match('#^\.\./vendor/\*/syn#', $path)
                || $this->globHasMatches(base_path('tests/'.$path)),
        ));
    }

    protected function globHasMatches(string $pattern): bool
    {
        return (glob($pattern, GLOB_ONLYDIR) ?: []) !== [];
    }

    protected function isPestAlreadyPatched(string $content): bool
    {
        foreach ($this->existingTestPaths() as $path) {
            if (! str_contains($content, $path)) {
                return false;
            }
        }

        return true;
    }

    protected function patchPestContent(string $content): ?string
    {
        // Pattern 1: pest()->extend()->use()->in(...) chain
        $pattern1 = '/(pest\(\)->extend\([^)]+\)\s*->use\([^)]+\)\s*->in\(\s*)(.*?)(\s*\);)/s';

        // Pattern 2: pest()->extend()->in(...) chain (without ->use())
        $pattern2 = '/(pest\(\)->extend\([^)]+\)\s*(?:\/\/[^\n]*\n\s*)?->in\(\s*)(.*?)(\s*\);)/s';

        $pattern = null;
        $matches = [];

        if (preg_match($pattern1, $content, $matches)) {
            $pattern = $pattern1;
        } elseif (preg_match($pattern2, $content, $matches)) {
            $pattern = $pattern2;
        } else {
            return null;
        }

        $beforeIn = $matches[1];
        $existingPaths = $matches[2];
        $afterIn = $matches[3];

        $existingPathsArray = $this->parseExistingPestPaths($existingPaths);
        $allPaths = array_unique(array_merge($existingPathsArray, $this->existingTestPaths()));
        $newPathsString = $this->buildPestPathsString($allPaths);
        $newContent = $beforeIn.$newPathsString.$afterIn;

        return preg_replace($pattern, $newContent, $content);
    }

    /** @return array<int, string> */
    protected function parseExistingPestPaths(string $pathsString): array
    {
        preg_match_all("/'([^']+)'/", $pathsString, $matches);

        return $matches[1];
    }

    /** @param  array<int, string>  $paths */
    protected function buildPestPathsString(array $paths): string
    {
        $indent = "\n        ";
        $lines = [];

        foreach ($paths as $path) {
            $lines[] = "'{$path}'";
        }

        return $indent.implode(','.$indent, $lines);
    }

    protected function patchPhpunitXml(): bool
    {
        $phpunitPath = base_path('phpunit.xml');

        if (! file_exists($phpunitPath)) {
            return false;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (! @$dom->load($phpunitPath)) {
            $this->components->error('Failed to parse phpunit.xml.');

            return false;
        }

        $modified = false;

        if ($this->ensureTestSuites($dom)) {
            $modified = true;
        }

        if ($this->ensurePhpEnvVars($dom)) {
            $modified = true;
        }

        if ($modified) {
            $this->createBackup($phpunitPath);
            $dom->save($phpunitPath);
        }

        return $modified;
    }

    protected function ensureTestSuites(\DOMDocument $dom): bool
    {
        $modified = false;
        $xpath = new \DOMXPath($dom);

        $testsuitesNode = ($xpath->query('/phpunit/testsuites') ?: null)?->item(0);

        if (! $testsuitesNode instanceof \DOMElement) {
            $testsuites = $dom->createElement('testsuites');
            $dom->documentElement->appendChild($testsuites);
            $modified = true;
        } else {
            $testsuites = $testsuitesNode;
        }

        $requiredSuites = [
            'Modules' => [
                'synapps/modules/*/tests',
            ],
            'Packages' => array_values(array_filter(
                ['vendor/*/synmod-*/tests', 'vendor/*/synmodule-*/tests', 'vendor/*/synapps-*/tests'],
                fn (string $dir): bool => $this->globHasMatches(base_path($dir)),
            )),
        ];

        if (! $this->testsuiteExists($xpath, 'Modules')) {
            $this->addTestSuite($dom, $testsuites, 'Modules', $requiredSuites['Modules']);
            $modified = true;
        }

        if ($requiredSuites['Packages'] !== [] && ! $this->testsuiteExists($xpath, 'Packages')) {
            $this->addTestSuite($dom, $testsuites, 'Packages', $requiredSuites['Packages']);
            $modified = true;
        }

        return $modified;
    }

    protected function testsuiteExists(\DOMXPath $xpath, string $name): bool
    {
        $result = $xpath->query("/phpunit/testsuites/testsuite[@name='{$name}']");

        return $result !== false && $result->length > 0;
    }

    /**
     * @param  array<int, string>  $directories
     * @param  array<int, string>  $excludes
     */
    protected function addTestSuite(\DOMDocument $dom, \DOMElement $testsuites, string $name, array $directories, array $excludes = []): void
    {
        $testsuite = $dom->createElement('testsuite');
        $testsuite->setAttribute('name', $name);

        foreach ($directories as $dir) {
            $testsuite->appendChild($dom->createElement('directory', $dir));
        }

        foreach ($excludes as $exclude) {
            $testsuite->appendChild($dom->createElement('exclude', $exclude));
        }

        $testsuites->appendChild($testsuite);
    }

    protected function ensurePhpEnvVars(\DOMDocument $dom): bool
    {
        $modified = false;
        $xpath = new \DOMXPath($dom);

        $phpNode = ($xpath->query('/phpunit/php') ?: null)?->item(0);

        if (! $phpNode instanceof \DOMElement) {
            $php = $dom->createElement('php');
            $dom->documentElement->appendChild($php);
            $modified = true;
        } else {
            $php = $phpNode;
        }

        // [name => [value, force]]
        $requiredEnvVars = [
            'APP_ENV' => ['testing', false],
            'APP_LOCALE' => ['en', true],
            'APP_FALLBACK_LOCALE' => ['en', true],
            'DB_CONNECTION' => ['sqlite', false],
            'DB_DATABASE' => [':memory:', false],
            'CACHE_STORE' => ['array', false],
            'SESSION_DRIVER' => ['array', false],
            'QUEUE_CONNECTION' => ['sync', false],
            'MAIL_MAILER' => ['array', false],
            'BCRYPT_ROUNDS' => ['4', false],
        ];

        foreach ($requiredEnvVars as $name => [$value, $force]) {
            if ($this->ensureEnvVar($dom, $xpath, $php, $name, $value, $force)) {
                $modified = true;
            }
        }

        return $modified;
    }

    protected function ensureEnvVar(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $php, string $name, string $value, bool $force = false): bool
    {
        $envVars = $xpath->query("/phpunit/php/env[@name='{$name}']");

        if ($envVars !== false && $envVars->length > 0) {
            $envVar = $envVars->item(0);

            if (! $envVar instanceof \DOMElement) {
                return false;
            }

            $changed = false;

            if ($envVar->getAttribute('value') !== $value) {
                $envVar->setAttribute('value', $value);
                $changed = true;
            }

            $currentForce = $envVar->getAttribute('force');

            if ($force && $currentForce !== 'true') {
                $envVar->setAttribute('force', 'true');
                $changed = true;
            } elseif (! $force && $currentForce === 'true') {
                $envVar->removeAttribute('force');
                $changed = true;
            }

            return $changed;
        }

        $env = $dom->createElement('env');
        $env->setAttribute('name', $name);
        $env->setAttribute('value', $value);

        if ($force) {
            $env->setAttribute('force', 'true');
        }

        $php->appendChild($env);

        return true;
    }
}
