<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynAppsLinkErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:link-errors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create symlink from resources/views/errors to synapps/resources/views/errors';

    public function handle(): int
    {
        $target = synapps_path('resources/views/errors');
        $link = resource_path('views/errors');

        // Check if synapps/resources/views/errors exists
        if (! file_exists($target)) {
            $this->components->error('Source directory does not exist: '.$target);
            $this->components->info('Please ensure synapps/resources/views/errors directory exists first.');

            return 1;
        }

        // Check if link already exists and remove it
        if (file_exists($link) || is_link($link)) {
            $this->components->info('Existing link/directory found. Removing...');

            // Try to remove it
            if (! $this->removeLink($link)) {
                $this->components->error('Failed to remove existing link/directory.');
                $this->components->warn('Please manually remove: '.$link);

                return 1;
            }
        }

        // Try to create the link
        $success = $this->createLink($target, $link);

        if ($success) {
            $this->components->success('Link created successfully!');
            $this->components->info('resources/views/errors → synapps/resources/views/errors');

            return 0;
        }

        $this->components->error('Failed to create link.');

        return 1;
    }

    private function createLink(string $target, string $link): bool
    {
        // Try symlink first (Unix/Linux, or Windows with admin)
        try {
            if (@symlink($target, $link)) {
                return true;
            }
        } catch (\Exception $e) {
            // Symlink failed, continue to next method
        }

        // Try junction on Windows (doesn't require admin)
        if (PHP_OS_FAMILY === 'Windows') {
            $this->components->info('Symlink failed, trying Windows junction...');
            $targetPath = str_replace('/', '\\', realpath($target));
            $linkPath = str_replace('/', '\\', $link);

            exec("mklink /J \"{$linkPath}\" \"{$targetPath}\" 2>&1", $output, $returnCode);

            if ($returnCode === 0) {
                return true;
            }

            $this->components->warn('Junction creation failed: '.implode(' ', $output));
        }

        return false;
    }

    private function removeLink(string $link): bool
    {
        clearstatcache(true, $link);

        // For symlinks (including broken ones), use unlink
        if (is_link($link)) {
            $result = @unlink($link);
            clearstatcache(true, $link);

            return $result;
        }

        // For Windows junctions or directories, use rmdir (no /S flag to only remove junction, not contents)
        if (PHP_OS_FAMILY === 'Windows' && (is_dir($link) || file_exists($link))) {
            $pathEscaped = str_replace('/', '\\', $link);
            exec("rmdir /Q \"{$pathEscaped}\" 2>&1", $output, $returnCode);
            clearstatcache(true, $link);

            return $returnCode === 0;
        }

        // For regular directories on Unix-like systems
        if (is_dir($link)) {
            $result = @rmdir($link);
            clearstatcache(true, $link);

            return $result;
        }

        return false;
    }
}
