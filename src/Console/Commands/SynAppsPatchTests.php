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
    protected $description = 'Configure testing infrastructure (Pest.php, .env.testing, phpunit.xml) for module tests';

    private array $pathsToAdd = [
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

        // 4. Patch phpunit.xml
        if (! $this->patchPhpunitXml()) {
            $success = false;
        }

        if ($success) {
            $this->newLine();
            $this->components->success('Test configuration completed successfully!');
            $this->components->info('✓ tests/Pest.php - Module paths added');
            $this->components->info('✓ .env.testing - In-memory database configured');
            $this->components->info('✓ composer.json - Test script updated');
            $this->components->info('✓ phpunit.xml - Test suites and environment configured');
        }

        return $success ? 0 : 1;
    }

    /**
     * Create a backup of a file before patching.
     * Only creates the backup once — skips if a .bak already exists.
     */
    private function createBackup(string $path): void
    {
        if (! file_exists($path)) {
            return;
        }

        $backupPath = $path.'.bak';

        if (! file_exists($backupPath)) {
            copy($path, $backupPath);
        }
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
        $this->createBackup($pestPath);
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

        $defaultContent = <<<'ENV'
APP_ENV=testing
APP_KEY=
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
            if (! file_put_contents($envTestingPath, $defaultContent)) {
                $this->components->error('Failed to create .env.testing.');

                return false;
            }

            $this->components->success('.env.testing created successfully!');

            return true;
        }

        // File exists — find missing keys and append them
        $existingContent = file_get_contents($envTestingPath);

        /** @var array<string, string> $missingLines */
        $missingLines = [];

        foreach ($requiredKeys as $key => $value) {
            if (! preg_match('/^'.preg_quote($key, '/').'=/m', $existingContent)) {
                $missingLines[$key] = "{$key}={$value}";
            }
        }

        if (empty($missingLines)) {
            $this->components->info('.env.testing already configured correctly.');

            return true;
        }

        $this->createBackup($envTestingPath);
        $appendContent = "\n".implode("\n", $missingLines)."\n";

        if (! file_put_contents($envTestingPath, rtrim($existingContent)."\n".$appendContent)) {
            $this->components->error('Failed to update .env.testing.');

            return false;
        }

        $this->components->success('.env.testing updated — added: '.implode(', ', array_keys($missingLines)));

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

        $this->createBackup($composerPath);
        if (! file_put_contents($composerPath, $newComposerContent)) {
            $this->components->error('Failed to update composer.json.');

            return false;
        }

        $this->components->success('composer.json test script updated successfully!');

        return true;
    }

    private function isAlreadyPatched(string $content): bool
    {
        // Check if ALL paths we want to add are already present
        foreach ($this->pathsToAdd as $path) {
            if (! str_contains($content, $path)) {
                return false; // At least one path is missing
            }
        }

        return true; // All paths are present
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

    private function patchPhpunitXml(): bool
    {
        $phpunitPath = base_path('phpunit.xml');

        // Check if phpunit.xml exists
        if (! file_exists($phpunitPath)) {
            $this->components->warn('phpunit.xml not found. Skipping.');

            return true; // Not critical, skip
        }

        // Load the XML file
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        if (! @$dom->load($phpunitPath)) {
            $this->components->error('Failed to parse phpunit.xml.');

            return false;
        }

        $modified = false;

        // 1. Ensure testsuites section exists and patch it
        if ($this->ensureTestSuites($dom)) {
            $modified = true;
        }

        // 2. Ensure php environment section has correct values
        if ($this->ensurePhpEnvVars($dom)) {
            $modified = true;
        }

        // Save if modified
        if ($modified) {
            $this->createBackup($phpunitPath);
            if (! $dom->save($phpunitPath)) {
                $this->components->error('Failed to save phpunit.xml.');

                return false;
            }

            $this->components->success('phpunit.xml patched successfully!');
        } else {
            $this->components->info('phpunit.xml already configured correctly.');
        }

        return true;
    }

    private function ensureTestSuites(\DOMDocument $dom): bool
    {
        $modified = false;
        $xpath = new \DOMXPath($dom);

        // Find or create testsuites element
        $testsuitesNode = $xpath->query('/phpunit/testsuites')->item(0);

        if (! $testsuitesNode instanceof \DOMElement) {
            $testsuites = $dom->createElement('testsuites');
            $dom->documentElement->appendChild($testsuites);
            $modified = true;
        } else {
            $testsuites = $testsuitesNode;
        }

        // Define required testsuites
        $requiredSuites = [
            'Modules' => [
                'synapps/modules/*/tests',
            ],
            'Packages' => [
                'vendor/*/synmod-*/tests',
                'vendor/*/synmodule-*/tests',
                'vendor/*/synapps-*/tests',
            ],
        ];

        // Check and add Modules testsuite
        if (! $this->testsuiteExists($xpath, 'Modules')) {
            $this->addTestSuite($dom, $testsuites, 'Modules', $requiredSuites['Modules']);
            $modified = true;
        }

        // Check and add Packages testsuite
        if (! $this->testsuiteExists($xpath, 'Packages')) {
            $this->addTestSuite($dom, $testsuites, 'Packages', $requiredSuites['Packages']);
            $modified = true;
        }

        return $modified;
    }

    private function testsuiteExists(\DOMXPath $xpath, string $name): bool
    {
        $result = $xpath->query("/phpunit/testsuites/testsuite[@name='{$name}']");

        return $result->length > 0;
    }

    private function addTestSuite(\DOMDocument $dom, \DOMElement $testsuites, string $name, array $directories, array $excludes = []): void
    {
        $testsuite = $dom->createElement('testsuite');
        $testsuite->setAttribute('name', $name);

        foreach ($directories as $dir) {
            $directory = $dom->createElement('directory', $dir);
            $testsuite->appendChild($directory);
        }

        foreach ($excludes as $exclude) {
            $excludeElement = $dom->createElement('exclude', $exclude);
            $testsuite->appendChild($excludeElement);
        }

        $testsuites->appendChild($testsuite);
    }

    private function ensurePhpEnvVars(\DOMDocument $dom): bool
    {
        $modified = false;
        $xpath = new \DOMXPath($dom);

        // Find or create php element
        $phpNode = $xpath->query('/phpunit/php')->item(0);

        if (! $phpNode instanceof \DOMElement) {
            $php = $dom->createElement('php');
            $dom->documentElement->appendChild($php);
            $modified = true;
        } else {
            $php = $phpNode;
        }

        // Define required environment variables — [name => [value, force]]
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

    private function ensureEnvVar(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $php, string $name, string $value, bool $force = false): bool
    {
        // Check if env var exists
        $envVars = $xpath->query("/phpunit/php/env[@name='{$name}']");

        if ($envVars->length > 0) {
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

        // Create new env var
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
