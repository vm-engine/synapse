<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Services\SynapseUtils;

class SynAppsCopyThemes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:copy-themes
                            {theme?* : Theme name(s) to copy (e.g. default application). Omit to copy all.}
                            {--force : Overwrite existing theme directories}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy stub themes from the Synapse package into synapps/resources/themes';

    public function handle(): int
    {
        $stubsBase = realpath(__DIR__.DS.'..'.DS.'..'.DS.'..'.DS.'stubs'.DS.'themes');

        if (! $stubsBase || ! is_dir($stubsBase)) {
            $this->components->error('Themes stubs directory not found.');

            return 1;
        }

        $available = $this->listAvailableThemes($stubsBase);

        if (empty($available)) {
            $this->components->warn('No stub themes found.');

            return 0;
        }

        $requested = array_filter((array) $this->argument('theme'));

        $themes = $this->resolveThemes($requested, $available);

        if (empty($themes)) {
            return 0;
        }

        $force = (bool) $this->option('force');
        $success = true;

        foreach ($themes as $theme) {
            if (! $this->copyTheme($stubsBase, $theme, $force)) {
                $success = false;
            }
        }

        if ($success) {
            $this->newLine();
            $this->components->success('Theme(s) copied successfully.');
            $this->components->info('Run "npm run build" or "composer run dev" to compile assets.');
        }

        return $success ? 0 : 1;
    }

    /**
     * @return array<string>
     */
    private function listAvailableThemes(string $stubsBase): array
    {
        $themes = [];

        foreach (scandir($stubsBase) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($stubsBase.DS.$entry)) {
                $themes[] = $entry;
            }
        }

        return $themes;
    }

    /**
     * @param  array<string>  $requested
     * @param  array<string>  $available
     * @return array<string>
     */
    private function resolveThemes(array $requested, array $available): array
    {
        if (empty($requested)) {
            // No argument given — copy all
            return $available;
        }

        $invalid = array_diff($requested, $available);

        if (! empty($invalid)) {
            $this->components->error('Unknown theme(s): '.implode(', ', $invalid));
            $this->components->info('Available: '.implode(', ', $available));

            return [];
        }

        return $requested;
    }

    private function copyTheme(string $stubsBase, string $theme, bool $force): bool
    {
        $source = $stubsBase.DS.$theme;
        $destination = synapps_path('resources'.DS.'themes'.DS.$theme);

        if (is_dir($destination) && ! $force) {
            $this->components->warn("Theme [{$theme}] already exists. Use --force to overwrite.");

            return true; // Not a failure, just skipped
        }

        if (is_dir($destination) && $force) {
            $this->removeDirectory($destination);
        }

        if (! SynapseUtils::copyDirectory($source, $destination)) {
            $this->components->error("Failed to copy theme [{$theme}].");

            return false;
        }

        $this->components->info("Theme [{$theme}] copied to synapps/resources/themes/{$theme}");

        return true;
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir.DS.$item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
