<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Events\ExcelImportCompleted;
use VmEngine\Synapse\Events\ExcelImportFailed;
use VmEngine\Synapse\Models\ExcelImport;
use VmEngine\Synapse\Services\Excel\Contracts\ProcessesImport;
use VmEngine\Synapse\Services\Excel\Readers\SpoutReader;
use VmEngine\Synapse\Services\Excel\Support\ChunkIterator;
use VmEngine\Synapse\Services\Excel\Support\ColumnMapper;

class ProcessExcelImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    /** @var array<int, int> */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly int $taskId,
    ) {}

    public function handle(): void
    {
        $task = ExcelImport::findOrFail($this->taskId);

        if ($task->status === ExcelTaskStatus::Cancelled) {
            return;
        }

        $task->update([
            'status' => ExcelTaskStatus::Processing,
            'started_at' => $task->started_at ?? now(),
            'last_heartbeat_at' => now(),
        ]);

        $options = $task->options ?? [];
        $chunkSize = $options['chunk_size'] ?? 1000;
        $startingRow = $options['starting_row'] ?? 1;
        $mapping = $options['mapping'] ?? null;

        $columnMapper = ColumnMapper::fromSerializable($mapping);
        $processor = $this->resolveProcessor($task->processor_class);

        $reader = new SpoutReader;
        $skipRows = 0;

        // Resume from failed_at_row if available
        if ($task->failed_at_row !== null) {
            $skipRows = $task->processed_rows;
        }

        try {
            $reader->open($task->file_path);

            // Count total rows first if not set
            if ($task->total_rows === null) {
                $totalRows = 0;
                foreach ($reader->rows() as $rowNumber => $rawRow) {
                    if ($rowNumber >= $startingRow) {
                        $totalRows++;
                    }
                }
                $task->update(['total_rows' => $totalRows]);
                $reader->close();

                // Re-open for processing
                $reader->open($task->file_path);
            }

            $mappedRows = $this->getMappedRowsGenerator($reader, $startingRow, $columnMapper);

            ChunkIterator::process(
                $mappedRows,
                $chunkSize,
                function ($rows, $chunkIndex) use ($task, $processor) {
                    $processor->process($rows, $chunkIndex);
                    $task->increment('processed_rows', $rows->count(), ['last_heartbeat_at' => now()]);
                    $task->refresh();

                    // Check for cancellation between chunks
                    if ($task->status === ExcelTaskStatus::Cancelled) {
                        return;
                    }
                },
                $skipRows,
            );

            $reader->close();

            $task->markCompleted();
            event(new ExcelImportCompleted($task));
        } catch (Throwable $e) {
            $reader->close();

            $task->markFailed(
                $e->getMessage(),
                $task->processed_rows > 0 ? $task->processed_rows : null,
            );

            event(new ExcelImportFailed($task, $e));

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $task = ExcelImport::find($this->taskId);

        if ($task !== null && $task->status === ExcelTaskStatus::Processing) {
            $task->markFailed($exception->getMessage(), $task->processed_rows > 0 ? $task->processed_rows : null);
            event(new ExcelImportFailed($task, $exception));
        }
    }

    /**
     * @return \Generator<int, array<array-key, mixed>>
     */
    private function getMappedRowsGenerator(SpoutReader $reader, int $startingRow, ColumnMapper $mapper): \Generator
    {
        foreach ($reader->rows() as $rowNumber => $rawRow) {
            if ($rowNumber < $startingRow) {
                continue;
            }

            yield $mapper->map($rawRow, $rowNumber);
        }
    }

    private function resolveProcessor(?string $processorClass): ProcessesImport
    {
        if ($processorClass === null) {
            throw new \RuntimeException('No processor class specified for background import.');
        }

        if (! class_exists($processorClass)) {
            throw new \RuntimeException("Processor class not found: {$processorClass}");
        }

        $instance = app($processorClass);

        if (! $instance instanceof ProcessesImport) {
            throw new \RuntimeException("{$processorClass} must implement ".ProcessesImport::class);
        }

        return $instance;
    }
}
