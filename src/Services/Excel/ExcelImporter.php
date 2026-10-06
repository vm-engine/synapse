<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel;

use Closure;
use Illuminate\Support\Collection;
use RuntimeException;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Jobs\ProcessExcelImport;
use VmEngine\Synapse\Models\ExcelImport;
use VmEngine\Synapse\Services\Excel\Contracts\ProcessesImport;
use VmEngine\Synapse\Services\Excel\Contracts\ReaderInterface;
use VmEngine\Synapse\Services\Excel\Readers\SpoutReader;
use VmEngine\Synapse\Services\Excel\Support\ChunkIterator;
use VmEngine\Synapse\Services\Excel\Support\ColumnMapper;

class ExcelImporter
{
    private string $filePath;

    private int $startingRow = 1;

    private ColumnMapper $columnMapper;

    private ?int $userId = null;

    private ?string $reference = null;

    private ?string $queue = null;

    private ?ReaderInterface $reader = null;

    private function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->columnMapper = new ColumnMapper;
    }

    /**
     * Create a new importer for the given file.
     */
    public static function make(string $filePath): self
    {
        return new self($filePath);
    }

    /**
     * Set the row number where data starts (1-based).
     */
    public function startingRow(int $row): self
    {
        $this->startingRow = $row;

        return $this;
    }

    /**
     * Set column mapping.
     *
     * @param  array<int|string, int|string>|Closure  $mapping
     */
    public function mapColumns(array|Closure $mapping): self
    {
        $this->columnMapper->setMapping($mapping);

        return $this;
    }

    /**
     * Set the user ID for tracking.
     */
    public function forUser(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Set a reference identifier for tracking the task across page refreshes.
     */
    public function reference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Set the queue name for background processing.
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Use a custom reader implementation.
     */
    public function useReader(ReaderInterface $reader): self
    {
        $this->reader = $reader;

        return $this;
    }

    /**
     * Import all rows synchronously and return as a collection.
     *
     * @return Collection<int, array<array-key, mixed>>
     */
    public function import(): Collection
    {
        $reader = $this->getReader();
        $rows = [];

        try {
            $reader->open($this->filePath);

            foreach ($reader->rows() as $rowNumber => $rawRow) {
                if ($rowNumber < $this->startingRow) {
                    continue;
                }

                $rows[] = $this->columnMapper->map($rawRow, $rowNumber);
            }
        } finally {
            $reader->close();
        }

        return collect($rows);
    }

    /**
     * Import in chunks for memory-efficient processing.
     *
     * @param  Closure(Collection<int, array<array-key, mixed>>, int): void  $callback
     * @return int Total rows processed
     */
    public function chunk(int $chunkSize, Closure $callback): int
    {
        $reader = $this->getReader();

        try {
            $reader->open($this->filePath);

            $mappedRows = $this->getMappedRowsGenerator($reader);

            return ChunkIterator::process($mappedRows, $chunkSize, $callback);
        } finally {
            $reader->close();
        }
    }

    /**
     * Dispatch a background import job.
     *
     * @param  class-string<ProcessesImport>  $processorClass
     */
    public function dispatch(string $processorClass, int $chunkSize = 1000): ExcelImport
    {
        if (! is_subclass_of($processorClass, ProcessesImport::class)) {
            throw new RuntimeException("{$processorClass} must implement ".ProcessesImport::class);
        }

        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Pending,
            'file_path' => $this->filePath,
            'user_id' => $this->userId,
            'reference' => $this->reference,
            'processor_class' => $processorClass,
            'options' => [
                'mapping' => $this->columnMapper->toSerializable(),
                'starting_row' => $this->startingRow,
                'chunk_size' => $chunkSize,
            ],
        ]);

        $job = new ProcessExcelImport($task->id);

        if ($this->queue !== null) {
            $job->onQueue($this->queue);
        }

        dispatch($job);

        return $task;
    }

    /**
     * Resume a failed or stale import task.
     */
    public static function resume(ExcelImport $task): ExcelImport
    {
        if ($task->status === ExcelTaskStatus::Cancelled) {
            throw new RuntimeException('Cannot resume a cancelled import task.');
        }

        if ($task->status === ExcelTaskStatus::Completed) {
            throw new RuntimeException('Cannot resume a completed import task.');
        }

        // Handle stale processing tasks
        if ($task->status === ExcelTaskStatus::Processing && $task->isStale()) {
            $task->markFailed('Task was stale (no heartbeat). Marked as failed for resume.');
            $task->refresh();
        }

        if ($task->status !== ExcelTaskStatus::Failed) {
            throw new RuntimeException("Cannot resume import task with status: {$task->status->value}");
        }

        $task->update(['status' => ExcelTaskStatus::Pending]);

        dispatch(new ProcessExcelImport($task->id));

        return $task->refresh();
    }

    /**
     * Get a generator of mapped rows from the reader.
     *
     * @return \Generator<int, array<array-key, mixed>>
     */
    private function getMappedRowsGenerator(ReaderInterface $reader): \Generator
    {
        foreach ($reader->rows() as $rowNumber => $rawRow) {
            if ($rowNumber < $this->startingRow) {
                continue;
            }

            yield $this->columnMapper->map($rawRow, $rowNumber);
        }
    }

    /**
     * Get the reader instance.
     */
    private function getReader(): ReaderInterface
    {
        return $this->reader ?? new SpoutReader;
    }
}
