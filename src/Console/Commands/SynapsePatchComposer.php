<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynapsePatchComposer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapse:patch-composer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge composer.local.json into composer.json for local development';

    public function handle(): int
    {
        $composerPath = base_path('composer.json');
        $composerLocalPath = base_path('composer.local.json');
        $composerOriginalPath = base_path('composer.original.json');
        $gitignorePath = base_path('.gitignore');

        // Check if composer.local.json exists
        if (! file_exists($composerLocalPath)) {
            $this->components->error('composer.local.json not found in project root.');
            $this->components->info('Please create composer.local.json with your local overrides first.');

            return 1;
        }

        // Check if composer.json exists
        if (! file_exists($composerPath)) {
            $this->components->error('composer.json not found in project root.');

            return 1;
        }

        // Check if already patched
        if (file_exists($composerOriginalPath)) {
            $this->components->warn('composer.original.json already exists. Composer may already be patched.');
            if (! $this->components->confirm('Do you want to re-patch from the current composer.json?', false)) {
                $this->components->info('Patch cancelled.');

                return 0;
            }
        }

        // Backup composer.json to composer.original.json
        $this->components->info('Backing up composer.json to composer.original.json...');
        if (! copy($composerPath, $composerOriginalPath)) {
            $this->components->error('Failed to backup composer.json');

            return 1;
        }

        // Read and decode both JSON files
        $composerContent = file_get_contents($composerPath);
        $composerLocalContent = file_get_contents($composerLocalPath);

        $composer = json_decode($composerContent, true);
        $composerLocal = json_decode($composerLocalContent, true);

        if ($composer === null) {
            $this->components->error('Failed to parse composer.json: '.json_last_error_msg());

            return 1;
        }

        if ($composerLocal === null) {
            $this->components->error('Failed to parse composer.local.json: '.json_last_error_msg());

            return 1;
        }

        // Merge composer.local.json into composer.json
        $this->components->info('Merging composer.local.json into composer.json...');
        $mergedComposer = $this->deepMerge($composer, $composerLocal);

        // Write merged content back to composer.json
        $mergedJson = json_encode($mergedComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($composerPath, $mergedJson) === false) {
            $this->components->error('Failed to write merged composer.json');

            return 1;
        }

        // Update .gitignore
        $this->updateGitignore($gitignorePath);

        $this->components->success('Composer patched successfully!');
        $this->components->info('composer.json has been updated with composer.local.json overrides.');
        $this->components->info('Original backed up to: composer.original.json');
        $this->newLine();
        $this->components->warn('Remember to run: composer update');

        return 0;
    }

    /**
     * Deep merge two arrays recursively
     */
    private function deepMerge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                // If both are arrays, merge recursively
                $base[$key] = $this->deepMerge($base[$key], $value);
            } else {
                // Otherwise, override the value
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Add entries to .gitignore if they don't exist
     */
    private function updateGitignore(string $gitignorePath): void
    {
        $entries = [
            'composer.local.json',
            'composer.original.json',
        ];

        $this->components->info('Updating .gitignore...');

        if (! file_exists($gitignorePath)) {
            // Create .gitignore if it doesn't exist
            file_put_contents($gitignorePath, implode(PHP_EOL, $entries).PHP_EOL);
            $this->components->info('Created .gitignore with entries.');

            return;
        }

        $gitignoreContent = file_get_contents($gitignorePath);
        $lines = explode(PHP_EOL, $gitignoreContent);
        $modified = false;

        foreach ($entries as $entry) {
            // Check if entry already exists (exact match or as pattern)
            $exists = false;
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === $entry || $trimmed === "/{$entry}") {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                $lines[] = $entry;
                $modified = true;
            }
        }

        if ($modified) {
            // Ensure single trailing newline
            $gitignoreContent = implode(PHP_EOL, array_filter($lines, fn ($line) => $line !== '')).PHP_EOL;
            file_put_contents($gitignorePath, $gitignoreContent);
            $this->components->info('Updated .gitignore with new entries.');
        } else {
            $this->components->info('.gitignore already contains required entries.');
        }
    }
}
