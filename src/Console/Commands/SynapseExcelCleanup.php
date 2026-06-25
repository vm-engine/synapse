<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Models\ExcelExport;
use VmEngine\Synapse\Models\ExcelImport;

class SynapseExcelCleanup extends Command
{
    /**
     * @var string
     */
    protected $signature = 'synapse:excel-cleanup
        {--timeout=3900 : Seconds after which a processing task is considered stale}';

    /**
     * @var string
     */
    protected $description = 'Mark stale Excel import/export tasks as failed';

    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $markedCount = 0;
        $cutoff = now()->subSeconds($timeout);

        $staleQuery = fn ($query) => $query
            ->where('status', ExcelTaskStatus::Processing)
            ->where(function ($q) use ($cutoff) {
                // No heartbeat yet: stale if started_at exceeds timeout
                $q->where(function ($q2) use ($cutoff) {
                    $q2->whereNull('last_heartbeat_at')
                        ->whereNotNull('started_at')
                        ->where('started_at', '<', $cutoff);
                    // Has heartbeat: stale if heartbeat exceeds timeout
                })->orWhere('last_heartbeat_at', '<', $cutoff);
            });

        // Clean up stale imports
        $staleQuery(ExcelImport::query())->cursor()->each(function ($task) use (&$markedCount) {
            $task->markFailed('Task marked as failed by cleanup: no heartbeat within timeout period.');
            $markedCount++;
            $this->line("Marked import #{$task->id} as failed (stale).");
        });

        // Clean up stale exports
        $staleQuery(ExcelExport::query())->cursor()->each(function ($task) use (&$markedCount) {
            $task->markFailed('Task marked as failed by cleanup: no heartbeat within timeout period.');
            $markedCount++;
            $this->line("Marked export #{$task->id} as failed (stale).");
        });

        if ($markedCount === 0) {
            $this->info('No stale tasks found.');
        } else {
            $this->info("Marked {$markedCount} stale task(s) as failed.");
        }

        return self::SUCCESS;
    }
}
