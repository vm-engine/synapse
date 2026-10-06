<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Models\ExcelExport;

/**
 * Async excel-export helpers for Livewire host components.
 *
 * Pair with <x-synapse-excel-export-button mode="async" ...> — the host
 * still declares its own #[Computed] wrapping excelExportTask() (the
 * reference string is app-specific) and its own action methods that
 * delegate to downloadExcelExportFile()/dismissExcelExportTask().
 *
 * @phpstan-ignore trait.unused
 */
trait WithExcelExport
{
    /**
     * Find the latest export task tracked under the given reference.
     */
    protected function excelExportTask(string $reference): ?ExcelExport
    {
        return ExcelExport::findByReference($reference);
    }

    /**
     * Download a completed task's output file, or notify if it's missing/not ready.
     */
    protected function downloadExcelExportFile(ExcelExport $task, string $filename, string $notFoundMessage = ''): ?BinaryFileResponse
    {
        if ($task->status !== ExcelTaskStatus::Completed || ! $task->output_path || ! file_exists($task->output_path)) {
            $this->dispatch(
                'notify',
                variant: 'error',
                title: 'Error',
                message: $notFoundMessage ?: 'Export file not found.',
            );

            return null;
        }

        return response()->download($task->output_path, $filename);
    }

    /**
     * Delete a task's output file (if any) and the task record itself.
     */
    protected function dismissExcelExportTask(ExcelExport $task): void
    {
        if ($task->output_path && file_exists($task->output_path)) {
            @unlink($task->output_path);
        }

        $task->delete();
    }
}
