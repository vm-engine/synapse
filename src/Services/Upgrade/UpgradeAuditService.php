<?php

namespace VmEngine\Synapse\Services\Upgrade;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use VmEngine\Synapse\Services\Upgrade\Rules\BladeTagNotationRule;
use VmEngine\Synapse\Services\Upgrade\Rules\JsLivewireHooksRule;
use VmEngine\Synapse\Services\Upgrade\Rules\RegisterMethodsRule;
use VmEngine\Synapse\Services\Upgrade\Rules\RouteGetLivewireCandidateRule;
use VmEngine\Synapse\Services\Upgrade\Rules\RouteLivewireNotationRule;

/**
 * Detects and (optionally) auto-fixes legacy vm-engine/synapse patterns in a
 * target project — used by the `synapps:upgrade-audit` artisan command.
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php,
 * a self-contained standalone script for projects that haven't upgraded far
 * enough to have this class autoloadable yet. Keep the rule set in both
 * files in sync when Synapse introduces new breaking changes.
 */
class UpgradeAuditService
{
    private const EXCLUDED_DIRS = ['vendor', 'node_modules', 'storage', 'bootstrap', '.git'];

    /**
     * @return array<int, UpgradeRule>
     */
    public function rules(): array
    {
        return [
            new BladeTagNotationRule,
            new RouteLivewireNotationRule,
            new RegisterMethodsRule,
            new JsLivewireHooksRule,
            new RouteGetLivewireCandidateRule,
        ];
    }

    /**
     * @param  array<int, string>|null  $onlyRuleIds
     */
    public function scan(string $projectRoot, ?array $onlyRuleIds = null): AuditReport
    {
        $projectRoot = rtrim($projectRoot, '/');
        $rules = $this->filterRules($onlyRuleIds);
        $findings = [];

        foreach ($this->findFiles($projectRoot) as $path) {
            if (! is_file($path) || ! is_readable($path)) {
                continue;
            }

            $contents = @file_get_contents($path);

            if ($contents === false) {
                continue;
            }

            $relativePath = ltrim(substr($path, strlen($projectRoot)), '/');
            $lines = explode("\n", $contents);

            foreach ($rules as $rule) {
                if (! $this->fileMatchesExtensions($path, $rule->extensions())) {
                    continue;
                }

                foreach ($lines as $index => $line) {
                    if ($rule->matchesLine($line)) {
                        $findings[] = new UpgradeFinding(
                            file: $relativePath,
                            line: $index + 1,
                            snippet: trim($line),
                            ruleId: $rule->id(),
                            category: $rule->category(),
                            label: $rule->label(),
                            autoFixable: $rule->isAutoFixable(),
                        );
                    }
                }
            }
        }

        return new AuditReport($this->detectVersion($projectRoot), $findings);
    }

    /**
     * Rewrites every file containing an auto-fixable finding. Writes a
     * `{file}.bak` backup before each rewrite. Only files matched by the
     * given (or all) auto-fixable rules are touched.
     *
     * @param  array<int, string>|null  $onlyRuleIds
     */
    public function apply(string $projectRoot, ?array $onlyRuleIds = null): ApplyReport
    {
        $projectRoot = rtrim($projectRoot, '/');
        $report = $this->scan($projectRoot, $onlyRuleIds);
        $rules = array_filter($this->filterRules($onlyRuleIds), fn (UpgradeRule $r) => $r->isAutoFixable());

        $files = $report->autoFixableFiles();
        $applied = [];
        $backedUp = [];
        $failed = [];

        foreach ($files as $relativePath) {
            $absolutePath = $projectRoot.'/'.$relativePath;
            $original = is_readable($absolutePath) ? @file_get_contents($absolutePath) : false;

            if ($original === false) {
                $failed[$relativePath] = 'Could not read file.';

                continue;
            }

            $backupPath = $absolutePath.'.bak';

            if (file_put_contents($backupPath, $original) === false) {
                $failed[$relativePath] = 'Could not write backup file.';

                continue;
            }

            $backedUp[] = $relativePath.'.bak';

            $fixed = $original;
            foreach ($rules as $rule) {
                if ($this->fileMatchesExtensions($absolutePath, $rule->extensions())) {
                    $fixed = $rule->fix($fixed);
                }
            }

            if (file_put_contents($absolutePath, $fixed) === false) {
                $failed[$relativePath] = 'Could not write fixed file.';

                continue;
            }

            $applied[] = $relativePath;
        }

        return new ApplyReport($applied, $backedUp, $failed);
    }

    /**
     * @param  array<int, string>|null  $onlyRuleIds
     * @return array<int, UpgradeRule>
     */
    private function filterRules(?array $onlyRuleIds): array
    {
        $rules = $this->rules();

        if ($onlyRuleIds === null || $onlyRuleIds === []) {
            return $rules;
        }

        return array_values(array_filter($rules, fn (UpgradeRule $r) => in_array($r->id(), $onlyRuleIds, true)));
    }

    /**
     * @return array<int, string>
     */
    private function findFiles(string $projectRoot): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if ($fileInfo->isDir()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            $relative = ltrim(substr($path, strlen($projectRoot)), '/');

            $excluded = false;
            foreach (self::EXCLUDED_DIRS as $dir) {
                if ($relative === $dir || str_starts_with($relative, $dir.'/') || str_contains($relative, '/'.$dir.'/')) {
                    $excluded = true;

                    break;
                }
            }

            if ($excluded) {
                continue;
            }

            $name = $fileInfo->getFilename();

            if (str_ends_with($name, '.blade.php') || str_ends_with($name, '.php') || str_ends_with($name, '.js')) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * @param  array<int, string>  $extensions
     */
    private function fileMatchesExtensions(string $path, array $extensions): bool
    {
        foreach ($extensions as $extension) {
            if (str_ends_with($path, '.'.$extension)) {
                return true;
            }
        }

        return false;
    }

    private function detectVersion(string $projectRoot): string
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
}
