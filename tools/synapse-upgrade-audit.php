#!/usr/bin/env php
<?php

/**
 * Synapse Upgrade Audit — standalone, zero-dependency script.
 *
 * Scans a project for legacy vm-engine/synapse patterns that need updating
 * before/while upgrading to the latest version, and writes a markdown report.
 * Makes NO changes to any file except the report itself.
 *
 * This script is intentionally self-contained (no `require`, no Composer
 * autoload, no framework) because it targets projects that are *still on
 * an old synapse version* and may not have a working/updated autoload for
 * this package at all. Copy this single file into the target project's
 * root and run:
 *
 *   php synapse-upgrade-audit.php [--path=.] [--output=SYNAPSE_UPGRADE_AUDIT.md]
 *
 * Sibling implementation: packages/synapse/src/Services/Upgrade/UpgradeAuditService.php
 * (used by the `synapps:upgrade-audit` artisan command once a project has
 * upgraded far enough to have that command available). Keep the rule table
 * in both files in sync when Synapse introduces new breaking changes.
 */

// ---------------------------------------------------------------------------
// Rule table — one entry per known breaking-change pattern.
// See UPGRADE.md / UPGRADE_GUIDE.md / CHANGELOG.md in packages/synapse for
// the source documentation each rule is derived from.
// ---------------------------------------------------------------------------

/**
 * @return array<int, array{
 *     id: string,
 *     category: string,
 *     label: string,
 *     pattern: string,
 *     autoFixable: bool,
 *     extensions: array<int, string>,
 * }>
 */
function synapseUpgradeAuditRules(): array
{
    return [
        [
            'id' => 'A',
            'category' => 'blade-tag-notation',
            'label' => 'Livewire Blade tag dot-notation (pre-v2.2.0) — e.g. <livewire:example.todo-form /> should be <livewire:example::todo-form />',
            // Matches <livewire:{module}.{rest} but not already-namespaced <livewire:{module}::{rest}
            'pattern' => '/<livewire:([a-zA-Z0-9_-]+)\.(?!:)([a-zA-Z0-9_.-]+)/',
            'autoFixable' => true,
            'extensions' => ['blade.php'],
        ],
        [
            'id' => 'B',
            'category' => 'route-livewire-notation',
            'label' => "Route::livewire() dotted string target (pre-v2.2.0) — e.g. Route::livewire('/todo', 'example.todo-form') should use 'example::todo-form'",
            'pattern' => '/Route::livewire\(\s*[\'"][^\'"]*[\'"]\s*,\s*[\'"]([a-zA-Z0-9_-]+)\.(?!:)([a-zA-Z0-9_.-]+)[\'"]/',
            'autoFixable' => true,
            'extensions' => ['php'],
        ],
        [
            'id' => 'C',
            'category' => 'register-methods',
            'label' => 'AutoRegistersComponents::registerMfcComponents()/registerLivewireComponents() (removed in v2.2.0) — replace with registerLivewireNamespace()',
            'pattern' => '/\$this->(registerMfcComponents|registerLivewireComponents)\(\s*\)\s*;/',
            'autoFixable' => true,
            'extensions' => ['php'],
        ],
        [
            'id' => 'D',
            'category' => 'js-livewire-hooks',
            'label' => "Livewire.hook('commit'|'morph.updated'|'message.processed', ...) (Livewire v3 API, removed in v2.0.0) — migrate to Livewire.interceptMessage()",
            'pattern' => '/Livewire\.hook\(\s*[\'"](commit|morph\.updated|message\.processed)[\'"]/',
            'autoFixable' => false,
            'extensions' => ['js'],
        ],
        [
            'id' => 'E',
            'category' => 'route-get-livewire-candidate',
            'label' => 'Route::get(SomeComponent::class) candidate (v2.0.0 introduced Route::livewire()) — review whether this should be Route::livewire()',
            'pattern' => '/Route::get\(\s*[\'"][^\'"]+[\'"]\s*,\s*([A-Za-z0-9_\\\\]+)::class\s*\)/',
            'autoFixable' => false,
            'extensions' => ['php'],
        ],
    ];
}

// ---------------------------------------------------------------------------
// CLI argument parsing
// ---------------------------------------------------------------------------

function synapseUpgradeAuditParseArgs(array $argv): array
{
    $options = [
        'path' => getcwd(),
        'output' => 'SYNAPSE_UPGRADE_AUDIT.md',
        'help' => false,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;

            continue;
        }

        if (strpos($arg, '--path=') === 0) {
            $options['path'] = rtrim(substr($arg, strlen('--path=')), '/');

            continue;
        }

        if (strpos($arg, '--output=') === 0) {
            $options['output'] = substr($arg, strlen('--output='));

            continue;
        }
    }

    return $options;
}

function synapseUpgradeAuditShowHelp(): void
{
    echo "Synapse Upgrade Audit\n\n";
    echo "Usage: php synapse-upgrade-audit.php [--path=.] [--output=SYNAPSE_UPGRADE_AUDIT.md]\n\n";
    echo "Scans the target project for legacy vm-engine/synapse patterns that need\n";
    echo "updating before/while upgrading to the latest version, and writes a markdown\n";
    echo "report. Makes NO changes to any file except the report itself.\n\n";
    echo "Options:\n";
    echo "  --path=<dir>      Project root to scan (default: current directory)\n";
    echo "  --output=<file>   Report file path, relative to --path (default: SYNAPSE_UPGRADE_AUDIT.md)\n";
    echo "  --help, -h        Show this help\n";
}

// ---------------------------------------------------------------------------
// Version detection (informational only)
// ---------------------------------------------------------------------------

function synapseUpgradeAuditDetectVersion(string $projectRoot): string
{
    $lockPath = $projectRoot.'/composer.lock';

    if (! is_file($lockPath)) {
        return 'unknown (no composer.lock found)';
    }

    $contents = file_get_contents($lockPath);
    $data = $contents !== false ? json_decode($contents, true) : null;

    if (! is_array($data)) {
        return 'unknown (composer.lock could not be parsed)';
    }

    foreach (['packages', 'packages-dev'] as $key) {
        foreach ($data[$key] ?? [] as $package) {
            if (($package['name'] ?? null) === 'vm-engine/synapse') {
                return (string) ($package['version'] ?? 'unknown');
            }
        }
    }

    return 'unknown (vm-engine/synapse not found in composer.lock)';
}

// ---------------------------------------------------------------------------
// File scanning
// ---------------------------------------------------------------------------

const SYNAPSE_UPGRADE_AUDIT_EXCLUDED_DIRS = ['vendor', 'node_modules', 'storage', 'bootstrap', '.git'];

/**
 * @return array<int, string> absolute file paths
 */
function synapseUpgradeAuditFindFiles(string $projectRoot): array
{
    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileInfo) {
        /** @var SplFileInfo $fileInfo */
        if ($fileInfo->isDir()) {
            if (in_array($fileInfo->getFilename(), SYNAPSE_UPGRADE_AUDIT_EXCLUDED_DIRS, true)) {
                $iterator->getInnerIterator()->next();

                continue;
            }

            continue;
        }

        $path = $fileInfo->getPathname();
        $relative = ltrim(substr($path, strlen($projectRoot)), DIRECTORY_SEPARATOR);

        foreach (SYNAPSE_UPGRADE_AUDIT_EXCLUDED_DIRS as $excluded) {
            if ($relative === $excluded
                || strpos($relative, $excluded.DIRECTORY_SEPARATOR) === 0
                || strpos($relative, DIRECTORY_SEPARATOR.$excluded.DIRECTORY_SEPARATOR) !== false) {
                continue 2;
            }
        }

        $name = $fileInfo->getFilename();

        if (str_ends_with($name, '.blade.php') || str_ends_with($name, '.php') || str_ends_with($name, '.js')) {
            $files[] = $path;
        }
    }

    return $files;
}

function synapseUpgradeAuditFileMatchesExtensions(string $path, array $extensions): bool
{
    foreach ($extensions as $extension) {
        if (str_ends_with($path, '.'.$extension)) {
            return true;
        }
    }

    return false;
}

/**
 * @return array<int, array{
 *     file: string,
 *     line: int,
 *     snippet: string,
 *     ruleId: string,
 *     category: string,
 *     label: string,
 *     autoFixable: bool,
 * }>
 */
function synapseUpgradeAuditScan(string $projectRoot): array
{
    $rules = synapseUpgradeAuditRules();
    $files = synapseUpgradeAuditFindFiles($projectRoot);
    $findings = [];

    foreach ($files as $path) {
        $contents = file_get_contents($path);

        if ($contents === false) {
            continue;
        }

        $relativePath = ltrim(substr($path, strlen($projectRoot)), DIRECTORY_SEPARATOR);
        $lines = explode("\n", $contents);

        foreach ($rules as $rule) {
            if (! synapseUpgradeAuditFileMatchesExtensions($path, $rule['extensions'])) {
                continue;
            }

            foreach ($lines as $index => $line) {
                if (preg_match($rule['pattern'], $line) === 1) {
                    $findings[] = [
                        'file' => $relativePath,
                        'line' => $index + 1,
                        'snippet' => trim($line),
                        'ruleId' => $rule['id'],
                        'category' => $rule['category'],
                        'label' => $rule['label'],
                        'autoFixable' => $rule['autoFixable'],
                    ];
                }
            }
        }
    }

    return $findings;
}

// ---------------------------------------------------------------------------
// Markdown report rendering
// ---------------------------------------------------------------------------

function synapseUpgradeAuditRenderMarkdown(string $installedVersion, array $findings): string
{
    $autoFixable = array_values(array_filter($findings, fn (array $f) => $f['autoFixable']));
    $manualReview = array_values(array_filter($findings, fn (array $f) => ! $f['autoFixable']));

    $lines = [];
    $lines[] = '# Synapse Upgrade Audit';
    $lines[] = '';
    $lines[] = 'Generated: '.date('Y-m-d H:i:s');
    $lines[] = 'Detected `vm-engine/synapse` version: '.$installedVersion;
    $lines[] = '';

    if ($findings === []) {
        $lines[] = 'No legacy patterns detected. ✓';
        $lines[] = '';

        return implode("\n", $lines);
    }

    $lines[] = '## Breaking changes found (auto-fixable)';
    $lines[] = '';

    if ($autoFixable === []) {
        $lines[] = '_None found._';
        $lines[] = '';
    } else {
        $byCategory = [];
        foreach ($autoFixable as $finding) {
            $byCategory[$finding['category']][] = $finding;
        }

        foreach ($byCategory as $category => $categoryFindings) {
            $lines[] = '### '.$category.' ('.$categoryFindings[0]['ruleId'].')';
            $lines[] = '';
            $lines[] = $categoryFindings[0]['label'];
            $lines[] = '';
            foreach ($categoryFindings as $finding) {
                $lines[] = sprintf('- `%s:%d` — `%s`', $finding['file'], $finding['line'], $finding['snippet']);
            }
            $lines[] = '';
        }
    }

    $lines[] = '## Manual review required';
    $lines[] = '';

    if ($manualReview === []) {
        $lines[] = '_None found._';
        $lines[] = '';
    } else {
        $byCategory = [];
        foreach ($manualReview as $finding) {
            $byCategory[$finding['category']][] = $finding;
        }

        foreach ($byCategory as $category => $categoryFindings) {
            $lines[] = '### '.$category.' ('.$categoryFindings[0]['ruleId'].')';
            $lines[] = '';
            $lines[] = $categoryFindings[0]['label'];
            $lines[] = '';
            foreach ($categoryFindings as $finding) {
                $lines[] = sprintf('- `%s:%d` — `%s`', $finding['file'], $finding['line'], $finding['snippet']);
            }
            $lines[] = '';
        }
    }

    return implode("\n", $lines);
}

// ---------------------------------------------------------------------------
// Entry point
// ---------------------------------------------------------------------------

function synapseUpgradeAuditMain(array $argv): int
{
    $options = synapseUpgradeAuditParseArgs($argv);

    if ($options['help']) {
        synapseUpgradeAuditShowHelp();

        return 0;
    }

    $projectRoot = realpath($options['path']);

    if ($projectRoot === false) {
        echo "✗ Error: path not found: {$options['path']}\n";

        return 1;
    }

    echo "🔍 Scanning {$projectRoot} ...\n";

    $installedVersion = synapseUpgradeAuditDetectVersion($projectRoot);
    $findings = synapseUpgradeAuditScan($projectRoot);

    $autoFixableCount = count(array_filter($findings, fn (array $f) => $f['autoFixable']));
    $manualCount = count($findings) - $autoFixableCount;

    echo "  Detected vm-engine/synapse version: {$installedVersion}\n";
    echo "  Auto-fixable findings: {$autoFixableCount}\n";
    echo "  Manual-review findings: {$manualCount}\n";

    $markdown = synapseUpgradeAuditRenderMarkdown($installedVersion, $findings);
    $outputPath = $options['output'];

    if (! str_starts_with($outputPath, '/')) {
        $outputPath = $projectRoot.'/'.$outputPath;
    }

    if (file_put_contents($outputPath, $markdown) === false) {
        echo "✗ Error: could not write report to {$outputPath}\n";

        return 1;
    }

    echo "✓ Report written to {$outputPath}\n";

    return 0;
}

exit(synapseUpgradeAuditMain($argv));
