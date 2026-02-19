<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynAppsPatchResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:patch-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integrate Synapse resources into app.js, app.css, vite.config.js (with watch ignore), and clean default Laravel files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsLine = "import '../../synapps/resources/js/synapps.js';";
        $cssLine = "@import '../../synapps/resources/css/synapps.css';";

        $jsPatched = false;
        $jsLines = file(resource_path('js/app.js'));
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
            $jsPatched = true;
        }

        $cssPatched = false;
        $cssLines = file(resource_path('css/app.css'));
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
            $cssPatched = true;
        }

        // Patch CSS @source directives for module files
        $cssSourcePatched = $this->patchCssSource();

        // Patch theme assets (CSS/JS)
        $themePatched = $this->patchThemeAssets();

        // Patch vite.config.js
        $vitePatched = $this->patchViteConfig();

        // Patch routes/web.php to disable default Laravel welcome route
        $routePatched = $this->patchWebRoutes();

        // Patch DatabaseSeeder to comment out default user seeder
        $seederPatched = $this->patchDatabaseSeeder();

        // Patch package.json to add 'npm run watch' script
        $packageJsonPatched = $this->patchPackageJson();

        // Patch app.js to add global JS helper functions
        $globalsPatched = $this->patchAppJsGlobals();

        $patches = [
            'app.js — synapps import' => $jsPatched,
            'app.css — synapps import' => $cssPatched,
            'app.css — @source directives' => $cssSourcePatched,
            'app.css / app.js — theme assets' => $themePatched,
            'vite.config.js' => $vitePatched,
            'routes/web.php' => $routePatched,
            'database/seeders/DatabaseSeeder.php' => $seederPatched,
            'package.json — watch script' => $packageJsonPatched,
            'app.js — global helpers' => $globalsPatched,
        ];

        foreach ($patches as $label => $patched) {
            if ($patched) {
                $this->components->info("Patched: {$label}");
            }
        }
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

        $content = file_get_contents($cssPath);

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

            $content = file_get_contents($cssPath);

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

            $content = file_get_contents($jsPath);

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

        $content = file_get_contents($viteConfigPath);

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

        $content = file_get_contents($webRoutesPath);
        $lines = file($webRoutesPath, FILE_IGNORE_NEW_LINES);

        // Check if default route exists and is not already commented
        $hasDefaultRoute = false;
        $defaultRoutePattern = "/Route::get\s*\(\s*['\"]\/['\"]\s*,\s*function\s*\(\s*\)/";

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

        $content = file_get_contents($seederPath);
        $lines = file($seederPath, FILE_IGNORE_NEW_LINES);

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

        $content = file_get_contents($jsPath);

        if (str_contains($content, $marker)) {
            return false;
        }

        $snippet = <<<'JS'

// Synapse global helpers
window.withBack = (url) => url + '?back=' + encodeURIComponent(window.location.href);
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

        $packageJson = json_decode(file_get_contents($packageJsonPath), true);

        if (! is_array($packageJson)) {
            return false;
        }

        // Ensure scripts section exists
        if (! isset($packageJson['scripts'])) {
            $packageJson['scripts'] = [];
        }

        // Check if watch script already exists
        if (
            isset($packageJson['scripts']['watch'])
            && $packageJson['scripts']['watch'] === 'vite build --watch'
        ) {
            return true;
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
}
