<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynAppsPatchTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:patch-tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Patch tests/Pest.php, .env.testing, and composer.json for proper test configuration';

    private array $pathsToAdd = [
        'Browser', // Add Browser directory for Pest Browser Testing
        '../synapps/modules/*/tests/Feature',
        '../vendor/*/synmod-*/tests/Feature',
        '../vendor/*/synmodule-*/tests/Feature',
        '../vendor/*/synapps-*/tests/Feature',
        '../synapps/modules/*/tests/Browser',
        '../vendor/*/synmod-*/tests/Browser',
        '../vendor/*/synmodule-*/tests/Browser',
        '../vendor/*/synapps-*/tests/Browser',
    ];

    public function handle(): int
    {
        $success = true;

        // 1. Patch tests/Pest.php
        if (! $this->patchPestFile()) {
            $success = false;
        }

        // 2. Create/update .env.testing
        if (! $this->ensureEnvTesting()) {
            $success = false;
        }

        // 3. Update composer.json test script
        if (! $this->ensureComposerTestScript()) {
            $success = false;
        }

        if ($success) {
            $this->newLine();
            $this->components->success('Test configuration completed successfully!');
            $this->components->info('✓ tests/Pest.php - Module paths added');
            $this->components->info('✓ .env.testing - In-memory database configured');
            $this->components->info('✓ composer.json - Test script updated');
        }

        return $success ? 0 : 1;
    }

    private function patchPestFile(): bool
    {
        $pestPath = base_path('tests/Pest.php');

        // Check if Pest.php exists
        if (! file_exists($pestPath)) {
            $this->components->error('tests/Pest.php not found.');
            $this->components->info('Please ensure Pest is installed and tests/Pest.php exists.');

            return false;
        }

        // Read the file
        $content = file_get_contents($pestPath);

        // Check if already patched
        if ($this->isAlreadyPatched($content)) {
            $this->components->info('tests/Pest.php is already patched with module paths.');

            return true;
        }

        // Patch the file
        $patchedContent = $this->patchContent($content);

        if ($patchedContent === null) {
            $this->components->error('Failed to patch tests/Pest.php.');
            $this->components->warn('Could not find the pest()->extend()->in() chain.');
            $this->components->info('Please manually add module test paths to tests/Pest.php');

            return false;
        }

        // Write back to file
        if (! file_put_contents($pestPath, $patchedContent)) {
            $this->components->error('Failed to write to tests/Pest.php.');

            return false;
        }

        $this->components->success('tests/Pest.php patched successfully!');

        return true;
    }

    private function ensureEnvTesting(): bool
    {
        $envTestingPath = base_path('.env.testing');

        $envTestingContent = <<<'ENV'
APP_ENV=testing
APP_KEY=

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

CACHE_STORE=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
MAIL_MAILER=array

BCRYPT_ROUNDS=4

ENV;

        // Check if file exists
        if (file_exists($envTestingPath)) {
            $existingContent = file_get_contents($envTestingPath);

            // Check if it already has the correct database configuration
            if (str_contains($existingContent, 'DB_DATABASE=:memory:')) {
                $this->components->info('.env.testing already configured correctly.');

                return true;
            }

            // Update existing file
            $this->components->warn('.env.testing exists but needs updating.');
        }

        // Create or update the file
        if (! file_put_contents($envTestingPath, $envTestingContent)) {
            $this->components->error('Failed to create/update .env.testing.');

            return false;
        }

        $this->components->success('.env.testing created/updated successfully!');

        return true;
    }

    private function ensureComposerTestScript(): bool
    {
        $composerPath = base_path('composer.json');

        if (! file_exists($composerPath)) {
            $this->components->error('composer.json not found.');

            return false;
        }

        $composerContent = file_get_contents($composerPath);
        $composer = json_decode($composerContent, true);

        if (! isset($composer['scripts']['test'])) {
            $this->components->warn('composer.json does not have a "test" script.');

            return true; // Not critical, skip
        }

        $testScript = $composer['scripts']['test'];

        // Check if it already has the correct configuration
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
            $this->components->info('composer.json test script already configured correctly.');

            return true;
        }

        // Update test script
        $composer['scripts']['test'] = [
            '@php artisan config:clear --ansi',
            '@php artisan config:cache --ansi',
            '@php artisan test --env=testing',
        ];

        // Write back to composer.json with pretty print
        $newComposerContent = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! file_put_contents($composerPath, $newComposerContent)) {
            $this->components->error('Failed to update composer.json.');

            return false;
        }

        $this->components->success('composer.json test script updated successfully!');

        return true;
    }

    private function isAlreadyPatched(string $content): bool
    {
        // Check if any of our module paths already exist
        return str_contains($content, 'synapps/modules/*/tests/Feature')
            || str_contains($content, 'synmod-*/tests/Feature');
    }

    private function patchContent(string $content): ?string
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

        $beforeIn = $matches[1]; // pest()->extend()->[use()]->in(
        $existingPaths = $matches[2]; // existing paths
        $afterIn = $matches[3]; // );

        // Parse existing paths
        $existingPathsArray = $this->parseExistingPaths($existingPaths);

        // Merge with module paths, avoiding duplicates
        $allPaths = array_unique(array_merge($existingPathsArray, $this->pathsToAdd));

        // Build the new paths string
        $newPathsString = $this->buildPathsString($allPaths);

        // Reconstruct the pest() chain
        $newContent = $beforeIn.$newPathsString.$afterIn;

        // Replace in original content
        return preg_replace($pattern, $newContent, $content);
    }

    private function parseExistingPaths(string $pathsString): array
    {
        $paths = [];
        // Match all quoted strings
        preg_match_all("/'([^']+)'/", $pathsString, $matches);

        foreach ($matches[1] as $path) {
            $paths[] = $path;
        }

        return $paths;
    }

    private function buildPathsString(array $paths): string
    {
        $indent = "\n        ";
        $lines = [];

        foreach ($paths as $path) {
            $lines[] = "'{$path}'";
        }

        return $indent.implode(','.$indent, $lines);
    }
}
