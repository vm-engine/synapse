<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Services\SynapseUtils;

use function Laravel\Prompts\multiselect;

class SynAppsCopyStubs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:copy-stubs
                            {module?* : Module name(s) to copy (e.g. home todos). Omit to pick interactively.}
                            {--force : Overwrite existing module directories}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy stub modules from the Synapse package into synapps/modules';

    public function handle(): int
    {
        $stubsBase = realpath(__DIR__.DS.'..'.DS.'..'.DS.'..'.DS.'stubs'.DS.'synapps'.DS.'modules');

        if (! $stubsBase || ! is_dir($stubsBase)) {
            $this->components->error('Stubs directory not found.');

            return 1;
        }

        $available = $this->listAvailableModules($stubsBase);

        if (empty($available)) {
            $this->components->warn('No stub modules found.');

            return 0;
        }

        $requested = (array) $this->argument('module');

        $modules = $this->resolveModules($requested, $available);

        if (empty($modules)) {
            return 0;
        }

        $force = (bool) $this->option('force');
        $success = true;

        foreach ($modules as $module) {
            if (! $this->copyModule($stubsBase, $module, $force)) {
                $success = false;
            }
        }

        if ($success) {
            $this->newLine();
            $this->components->success('Stub module(s) copied successfully.');
        }

        return $success ? 0 : 1;
    }

    /**
     * @return array<string>
     */
    private function listAvailableModules(string $stubsBase): array
    {
        $modules = [];

        foreach (scandir($stubsBase) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($stubsBase.DS.$entry)) {
                $modules[] = $entry;
            }
        }

        return $modules;
    }

    /**
     * @param  array<string>  $requested
     * @param  array<string>  $available
     * @return array<string>
     */
    private function resolveModules(array $requested, array $available): array
    {
        // Filter out empty strings (no argument passed)
        $requested = array_filter($requested);

        if (empty($requested)) {
            return multiselect(
                label: 'Select stub modules to copy',
                options: array_combine($available, $available),
                required: true,
            );
        }

        $invalid = array_diff($requested, $available);

        if (! empty($invalid)) {
            $this->components->error('Unknown stub module(s): '.implode(', ', $invalid));
            $this->components->info('Available: '.implode(', ', $available));

            return [];
        }

        return $requested;
    }

    private function copyModule(string $stubsBase, string $module, bool $force): bool
    {
        $source = $stubsBase.DS.$module;
        $destination = synapps_path('modules'.DS.$module);

        if (is_dir($destination) && ! $force) {
            $this->components->warn("Module [{$module}] already exists. Use --force to overwrite.");

            return true; // Not a failure, just skipped
        }

        if (! SynapseUtils::copyDirectory($source, $destination)) {
            $this->components->error("Failed to copy module [{$module}].");

            return false;
        }

        $this->components->info("Module [{$module}] copied to synapps/modules/{$module}");

        return true;
    }
}
