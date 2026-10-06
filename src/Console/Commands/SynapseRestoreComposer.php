<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynapseRestoreComposer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapse:restore-composer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore original composer.json from composer.original.json backup';

    public function handle(): int
    {
        $composerPath = base_path('composer.json');
        $composerOriginalPath = base_path('composer.original.json');

        // Check if composer.original.json exists
        if (! file_exists($composerOriginalPath)) {
            $this->components->error('composer.original.json not found.');
            $this->components->info('Nothing to restore. Either composer was never patched, or the backup was deleted.');

            return 1;
        }

        // Confirm restoration
        $this->components->warn('This will replace the current composer.json with the original backup.');
        if (! $this->components->confirm('Do you want to continue?', true)) {
            $this->components->info('Restore cancelled.');

            return 0;
        }

        // Delete current composer.json
        $this->components->info('Removing patched composer.json...');
        if (file_exists($composerPath)) {
            if (! unlink($composerPath)) {
                $this->components->error('Failed to delete composer.json');

                return 1;
            }
        }

        // Rename composer.original.json to composer.json
        $this->components->info('Restoring original composer.json...');
        if (! rename($composerOriginalPath, $composerPath)) {
            $this->components->error('Failed to restore composer.original.json');
            $this->components->warn('Your composer.json may be in an inconsistent state. Please check manually.');

            return 1;
        }

        $this->components->success('Composer restored successfully!');
        $this->components->info('composer.json has been restored from backup.');
        $this->components->info('composer.original.json has been removed.');
        $this->newLine();
        $this->components->warn('Remember to run: composer update');

        return 0;
    }
}
