<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

use function Laravel\Prompts\confirm;

class SynAppsRemoveModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:remove-module
        {moduleName : The folder name of the module under synapps/modules}
        {--permanent : Permanently delete the module instead of moving it to .trash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a Synapse module, cleaning up composer.json and compiled configs afterwards';

    public function handle(Filesystem $files): int
    {
        $moduleName = $this->argument('moduleName');

        if (! is_string($moduleName)) {
            $this->components->error('Module name must be a string.');

            return self::INVALID;
        }

        $modulePath = synapps_path('modules'.DS.$moduleName);

        if (! $files->isDirectory($modulePath) || ! $files->exists($modulePath.DS.'module.json')) {
            $this->components->error("Module \"{$moduleName}\" was not found at synapps/modules/{$moduleName}.");

            return self::FAILURE;
        }

        $permanent = (bool) $this->option('permanent');

        $confirmed = confirm(
            label: $permanent
                ? "Permanently delete module \"{$moduleName}\"? This cannot be undone."
                : "Move module \"{$moduleName}\" to .trash? It can be restored manually afterwards.",
            default: false,
        );

        if (! $confirmed) {
            $this->components->warn('Cancelled.');

            return self::SUCCESS;
        }

        if ($permanent) {
            $files->deleteDirectory($modulePath);
            $this->components->info("Permanently deleted module \"{$moduleName}\".");
        } else {
            $this->ensureTrashIgnored($files);

            $trashPath = base_path('.trash'.DS.$moduleName.'-'.now()->format('YmdHis'));

            if (! $files->isDirectory(base_path('.trash'))) {
                $files->makeDirectory(base_path('.trash'), 0755, true);
            }

            $files->moveDirectory($modulePath, $trashPath);
            $this->components->info("Moved module \"{$moduleName}\" to ".str_replace(base_path().DS, '', $trashPath));
        }

        $this->runDiscover();

        return self::SUCCESS;
    }

    /**
     * Add `/.trash` to the project's .gitignore if it isn't already there.
     * No-ops if .gitignore doesn't exist or already ignores it.
     */
    private function ensureTrashIgnored(Filesystem $files): void
    {
        $gitignorePath = base_path('.gitignore');

        if (! $files->exists($gitignorePath)) {
            return;
        }

        $contents = $files->get($gitignorePath);

        $lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        $alreadyIgnored = collect($lines)
            ->contains(fn (string $line) => trim($line) === '/.trash');

        if ($alreadyIgnored) {
            return;
        }

        $separator = ($contents === '' || str_ends_with($contents, "\n")) ? '' : "\n";
        $files->put($gitignorePath, $contents.$separator."/.trash\n");
    }

    /**
     * Rediscover modules so composer.json's autoload entries and compiled
     * configs no longer reference the removed module. A failure here is
     * reported but does not fail the command — the module removal itself
     * already succeeded.
     */
    private function runDiscover(): void
    {
        try {
            $this->call(SynAppsDiscover::class);
        } catch (Throwable $e) {
            $this->components->warn(
                "Module removed, but synapps:discover failed to run cleanly: {$e->getMessage()}. ".
                'Run `php artisan synapps:discover` manually to finish cleaning up composer.json and compiled configs.'
            );
        }
    }
}
