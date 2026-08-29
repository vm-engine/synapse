<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Services\Upgrade\UpgradeAuditService;
use VmEngine\Synapse\Services\Upgrade\UpgradeFinding;
use VmEngine\Synapse\Traits\HasTypedInput;

class SynAppsUpgradeAudit extends Command
{
    use HasTypedInput;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:upgrade-audit
        {--apply : Rewrite the auto-fixable findings instead of only reporting}
        {--only=* : Restrict to specific rule IDs (e.g. --only=A --only=B)}
        {--force : Skip the git-clean check and confirmation prompt (only relevant with --apply)}
        {--output=SYNAPSE_UPGRADE_AUDIT.md : Path to write the markdown report, relative to the project root}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit the project for legacy vm-engine/synapse patterns that need updating; pass --apply to auto-fix';

    public function handle(UpgradeAuditService $service): int
    {
        // --only=* is an array-mode option, so this is always array<string>.
        $only = $this->option('only');
        $onlyRuleIds = is_array($only) && $only !== [] ? $only : null;
        $projectRoot = base_path();

        $report = $service->scan($projectRoot, $onlyRuleIds);

        $this->components->info('Detected vm-engine/synapse version: '.$report->installedVersion);

        $autoFixable = $report->autoFixable();
        $manualReview = $report->manualReview();

        if ($report->findings === []) {
            $this->components->info('No legacy patterns detected.');
        } else {
            $this->newLine();
            $this->components->twoColumnDetail('<fg=yellow>Auto-fixable findings</>', (string) count($autoFixable));
            $this->components->twoColumnDetail('<fg=yellow>Manual review findings</>', (string) count($manualReview));

            foreach ([...$autoFixable, ...$manualReview] as $finding) {
                $this->components->twoColumnDetail(
                    sprintf('%s <fg=gray>(%s)</>', $finding->file.':'.$finding->line, $finding->ruleId),
                    $finding->autoFixable ? '<fg=green>auto-fixable</>' : '<fg=yellow>manual review</>'
                );
            }
        }

        $outputPath = $this->stringOption('output');
        if (! str_starts_with($outputPath, '/')) {
            $outputPath = $projectRoot.'/'.$outputPath;
        }

        file_put_contents($outputPath, $report->toMarkdown());
        $this->newLine();
        $this->components->info('Report written to '.$outputPath);

        if (! $this->option('apply')) {
            return 0;
        }

        return $this->applyFixes($service, $projectRoot, $onlyRuleIds, $autoFixable);
    }

    /**
     * @param  array<int, string>|null  $onlyRuleIds
     * @param  array<int, UpgradeFinding>  $autoFixable
     */
    private function applyFixes(UpgradeAuditService $service, string $projectRoot, ?array $onlyRuleIds, array $autoFixable): int
    {
        $this->newLine();

        if ($autoFixable === []) {
            $this->components->info('Nothing to auto-fix.');

            return 0;
        }

        if (! $this->option('force')) {
            $status = trim((string) shell_exec('cd '.escapeshellarg($projectRoot).' && git status --porcelain 2>/dev/null'));

            if ($status !== '') {
                $this->components->error('Working tree is not clean. Commit or stash your changes first, or pass --force.');

                return 1;
            }

            $fileCount = count(array_unique(array_map(fn ($f) => $f->file, $autoFixable)));
            $prompt = sprintf('Apply %d auto-fixable finding(s) across %d file(s)?', count($autoFixable), $fileCount);

            if (! $this->components->confirm($prompt)) {
                $this->components->info('Apply cancelled.');

                return 0;
            }
        }

        $applyReport = $service->apply($projectRoot, $onlyRuleIds);

        foreach ($applyReport->appliedFiles as $file) {
            $this->components->success('Fixed: '.$file);
        }

        foreach ($applyReport->failedFiles as $file => $error) {
            $this->components->error(sprintf('Failed: %s (%s)', $file, $error));
        }

        $this->newLine();
        $this->components->info(sprintf(
            '%d file(s) fixed, %d backup(s) written, %d failure(s).',
            $applyReport->appliedCount(),
            count($applyReport->backedUpFiles),
            $applyReport->failedCount()
        ));
        $this->components->warn('Re-run "synapps:upgrade-audit" (without --apply) to confirm the auto-fixable findings are gone, and run your test suite.');

        return $applyReport->failedCount() > 0 ? 1 : 0;
    }
}
