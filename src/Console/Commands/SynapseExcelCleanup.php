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

        // Clean up stale imports
        $staleImports = ExcelImport::where('status', ExcelTaskStatus::Processing)->get();

        foreach ($staleImports as $task) {
            if ($task->isStale($timeout)) {
                $task->markFailed('Task marked as failed by cleanup: no heartbeat within timeout period.');
                $markedCount++;
                $this->line("Marked import #{$task->id} as failed (stale).");
            }
        }

        // Clean up stale exports
        $staleExports = ExcelExport::where('status', ExcelTaskStatus::Processing)->get();

        foreach ($staleExports as $task) {
            if ($task->isStale($timeout)) {
                $task->markFailed('Task marked as failed by cleanup: no heartbeat within timeout period.');
                $markedCount++;
                $this->line("Marked export #{$task->id} as failed (stale).");
            }
        }

        if ($markedCount === 0) {
            $this->info('No stale tasks found.');
        } else {
            $this->info("Marked {$markedCount} stale task(s) as failed.");
        }

        return self::SUCCESS;
    }
}
