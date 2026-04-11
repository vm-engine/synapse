<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Events\ExcelExportCompleted;
use VmEngine\Synapse\Events\ExcelExportFailed;
use VmEngine\Synapse\Models\ExcelExport;
use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;
use VmEngine\Synapse\Services\Excel\Contracts\ExportQuery;
use VmEngine\Synapse\Services\Excel\Contracts\WriterInterface;
use VmEngine\Synapse\Services\Excel\Writers\SpoutWriter;
use VmEngine\Synapse\Services\Excel\Writers\StyledWriter;
use VmEngine\Synapse\Services\Excel\Writers\TemplateWriter;

class ProcessExcelExport implements ShouldQueue
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
        $task = ExcelExport::findOrFail($this->taskId);

        if ($task->status === ExcelTaskStatus::Cancelled) {
            return;
        }

        $task->update([
            'status' => ExcelTaskStatus::Processing,
            'started_at' => now(),
            'processed_rows' => 0,
            'last_heartbeat_at' => now(),
        ]);

        $options = $task->options ?? [];
        $columns = $options['columns'] ?? [];
        $startingRow = $options['starting_row'] ?? 1;
        $templatePath = $options['template'] ?? null;
        $sheetName = $options['sheet_name'] ?? null;
        $columnStyles = $options['column_styles'] ?? [];
        $headerStyle = $options['header_style'] ?? [];
        $filters = $options['filters'] ?? [];

        $query = $this->resolveQuery($task->query_class, $filters);
        $writer = $this->resolveWriter(
            $task->output_path,
            $templatePath,
            $startingRow,
            $columnStyles,
            $headerStyle,
            $this->getHeaderLabels($columns)
        );

        try {
            $outputDir = dirname($task->output_path);
            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $writer->open($task->output_path, $sheetName);

            // Write header row if not using a template
            if ($templatePath === null && $startingRow <= 1 && ! empty($columns)) {
                $writer->writeRow($this->getHeaderLabels($columns));
            }

            // Get total count
            $totalRows = $query->count();
            $task->update(['total_rows' => $totalRows]);

            $processedRows = 0;
            $heartbeatInterval = 100;

            foreach ($query->cursor() as $record) {
                $row = $this->extractRowData($record, $columns);
                $writer->writeRow($row);
                $processedRows++;

                if ($processedRows % $heartbeatInterval === 0) {
                    $task->update([
                        'processed_rows' => $processedRows,
                        'last_heartbeat_at' => now(),
                    ]);
                }
            }

            $writer->close();

            $task->update(['processed_rows' => $processedRows]);
            $task->markCompleted();
            event(new ExcelExportCompleted($task));
        } catch (Throwable $e) {
            $writer->close();

            $task->markFailed($e->getMessage());
            event(new ExcelExportFailed($task, $e));

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $task = ExcelExport::find($this->taskId);

        if ($task !== null && $task->status === ExcelTaskStatus::Processing) {
            $task->markFailed($exception->getMessage());
            event(new ExcelExportFailed($task, $exception));
        }
    }

    /**
     * Resolve the query from the ExportQuery class.
     *
     * Filters are passed to the constructor via Laravel's container.
     *
     * @param  array<string, mixed>  $filters
     */
    private function resolveQuery(?string $queryClass, array $filters = []): Builder|\Illuminate\Database\Query\Builder
    {
        if ($queryClass === null) {
            throw new \RuntimeException('No query class specified for background export.');
        }

        if (! class_exists($queryClass)) {
            throw new \RuntimeException("Query class not found: {$queryClass}");
        }

        // Pass filters to the constructor via Laravel's container
        $instance = app()->make($queryClass, $filters);

        if (! $instance instanceof ExportQuery) {
            throw new \RuntimeException("{$queryClass} must implement ".ExportQuery::class);
        }

        return $instance();
    }

    /**
     * Resolve the appropriate writer based on options.
     *
     * @param  array<string, array<string, mixed>>  $columnStyles
     * @param  array<string, mixed>  $headerStyle
     * @param  array<int, string>  $headerLabels
     */
    private function resolveWriter(
        ?string $outputPath,
        ?string $templatePath,
        int $startingRow,
        array $columnStyles = [],
        array $headerStyle = [],
        array $headerLabels = []
    ): WriterInterface {
        // Use StyledWriter when styles are set
        if (! empty($columnStyles) || ! empty($headerStyle)) {
            $writer = new StyledWriter;
            $writer->setStartingRow($startingRow);
            $writer->setTemplatePath($templatePath);
            $writer->setColumnStyles($columnStyles);
            $writer->setHeaderStyle($headerStyle);
            $writer->setHeaderLabels($headerLabels);

            return $writer;
        }

        // Template without styles uses TemplateWriter
        if ($templatePath !== null) {
            return new TemplateWriter($templatePath, $startingRow);
        }

        // Default to SpoutWriter for speed
        return new SpoutWriter;
    }

    /**
     * Get header labels from column configuration.
     *
     * @param  array<int|string, string|array<string, mixed>|null>  $columns
     * @return array<int, string>
     */
    private function getHeaderLabels(array $columns): array
    {
        $headers = [];

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                if (is_string($value)) {
                    $headers[] = $value;
                } elseif (is_array($value) && isset($value['field'])) {
                    $headers[] = $value['field'];
                } else {
                    $headers[] = "Column {$key}";
                }
            } else {
                $headers[] = $key;
            }
        }

        return $headers;
    }

    /**
     * Extract a row of data from a record using column config.
     *
     * @param  array<int|string, string|array<string, mixed>|null>  $columns
     * @return array<int, mixed>
     */
    private function extractRowData(mixed $record, array $columns): array
    {
        if (empty($columns)) {
            if ($record instanceof Model) {
                return array_values($record->toArray());
            }

            return array_values((array) $record);
        }

        $row = [];

        foreach ($columns as $value) {
            if ($value === null) {
                // Closure-based columns cannot be serialized - skip in background
                $row[] = null;
            } elseif (is_array($value)) {
                $row[] = $this->applyTransformer($value, $record);
            } elseif (is_string($value)) {
                $row[] = $this->resolveStringValue($record, $value);
            }
        }

        return $row;
    }

    /**
     * Resolve a string field value, supporting dot notation for relationships.
     */
    private function resolveStringValue(mixed $record, string $field): mixed
    {
        if (str_contains($field, '.')) {
            return data_get($record, $field);
        }

        if ($record instanceof Model) {
            return $record->getAttribute($field);
        }

        return $record->$field ?? null;
    }

    /**
     * Apply a column transformer to extract and transform a value.
     *
     * @param  array<string, mixed>  $definition
     */
    private function applyTransformer(array $definition, mixed $record): mixed
    {
        $field = $definition['field'] ?? null;
        /** @var class-string<ColumnTransformer>|null $transformerClass */
        $transformerClass = $definition['transformer'] ?? null;
        /** @var array<string, mixed> $config */
        $config = $definition['config'] ?? [];

        $value = $field !== null ? $this->resolveStringValue($record, $field) : null;

        if ($transformerClass !== null && is_subclass_of($transformerClass, ColumnTransformer::class)) {
            $transformer = new $transformerClass(...$config);

            return $transformer->transform($value, $record);
        }

        return $value;
    }
}
