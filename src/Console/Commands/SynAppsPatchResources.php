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
    protected $description = 'Patch CSS, JS, and Vite config to add Synapse resource files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $jsLine = "import '" . addslashes(synapps_path('resources' . DS . 'js' . DS . 'synapps.js')) . "';";
        // $cssLine = "@import '" . addslashes(synapps_path('resources' . DS . 'css' . DS . 'synapps.css')) . "';";
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
            file_put_contents(resource_path('css/app.css'), implode('', $cssLines));
            $cssPatched = true;
        }

        // Patch CSS @source directives for module files
        $cssSourcePatched = $this->patchCssSource();

        // Patch vite.config.js
        $vitePatched = $this->patchViteConfig();

        if ($jsPatched || $cssPatched || $cssSourcePatched || $vitePatched) {
            $this->components->info('Resource files patched.');
        }
    }

    /**
     * Patch app.css to add @source directives for module files
     */
    protected function patchCssSource(): bool
    {
        $cssPath = resource_path('css/app.css');

        if (!file_exists($cssPath)) {
            return false;
        }

        $content = file_get_contents($cssPath);

        // Define @source directives to add
        $sourceDirectives = [
            "@source '../../synapps/**/*.blade.php';",
            "@source '../../synapps/**/*.js';",
            "@source '../../vendor/vm-engine/*/**/*.blade.php';",
            "@source '../../vendor/vm-engine/*/**/*.js';",
            "@source '../../vendor/*/synmod-*/**/*.blade.php';",
            "@source '../../vendor/*/synmod-*/**/*.js';",
            "@source '../../vendor/*/synapps-*/**/*.blade.php';",
            "@source '../../vendor/*/synapps-*/**/*.js';"
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
            file_put_contents($cssPath, implode("\n", $lines));
        }

        return $anyPatched;
    }

    /**
     * Patch vite.config.js to add static asset copying
     */
    protected function patchViteConfig(): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (!file_exists($viteConfigPath)) {
            return false;
        }

        $content = file_get_contents($viteConfigPath);

        // Check if already patched
        if (strpos($content, 'vite-plugin-static-copy') !== false) {
            return false;
        }

        // Add imports after existing imports
        $importLines = [
            "import { viteStaticCopy } from 'vite-plugin-static-copy';",
            "import { glob } from 'glob';"
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

        // Find the plugins array and add viteStaticCopy
        $pluginConfig = <<<'PLUGIN'
viteStaticCopy({
            targets: [
                // Copy from synapps/resources/assets
                {
                    src: 'synapps/resources/assets/**/*',
                    dest: 'assets'
                },
                // Copy from vendor/*/synmod-*/resources/assets
                ...glob.sync('vendor/*/synmod-*/resources/assets').map(dir => ({
                    src: `${dir}/**/*`,
                    dest: 'assets'
                })),
                // Copy from vendor/vm-engine/synapps-*/resources/assets
                ...glob.sync('vendor/vm-engine/synapps-*/resources/assets').map(dir => ({
                    src: `${dir}/**/*`,
                    dest: 'assets'
                }))
            ]
        })
PLUGIN;

        // Find tailwindcss() line and add viteStaticCopy after it
        $updatedContent = implode("\n", $lines);
        $updatedContent = preg_replace(
            '/(tailwindcss\(\),)/',
            "$1\n        " . $pluginConfig . ",",
            $updatedContent
        );

        file_put_contents($viteConfigPath, $updatedContent);

        return true;
    }
}
