<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Jobs\ProcessExcelExport;
use VmEngine\Synapse\Models\ExcelExport;
use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;
use VmEngine\Synapse\Services\Excel\Contracts\ExportQuery;
use VmEngine\Synapse\Services\Excel\Contracts\WriterInterface;
use VmEngine\Synapse\Services\Excel\Writers\SpoutWriter;
use VmEngine\Synapse\Services\Excel\Writers\StyledWriter;
use VmEngine\Synapse\Services\Excel\Writers\TemplateWriter;

class ExcelExporter
{
    /** @var EloquentBuilder<covariant Model>|QueryBuilder|null */
    private EloquentBuilder|QueryBuilder|null $query = null;

    /** @var class-string<ExportQuery>|null */
    private ?string $queryClass = null;

    /** @var array<int|string, string|Closure|array<string, mixed>> */
    private array $columns = [];

    private int $startingRow = 1;

    private ?string $reference = null;

    private ?string $sheetName = null;

    private ?string $templatePath = null;

    private ?int $userId = null;

    private ?string $queue = null;

    private ?WriterInterface $writer = null;

    /** @var array<string, array<string, mixed>> */
    private array $columnStyles = [];

    /** @var array<string, mixed> */
    private array $headerStyle = [];

    /** @var array<string, mixed> */
    private array $filters = [];

    /** @param  EloquentBuilder<covariant Model>|QueryBuilder|string  $source */
    private function __construct(EloquentBuilder|QueryBuilder|string $source)
    {
        if (is_string($source)) {
            if (! is_subclass_of($source, ExportQuery::class)) {
                throw new RuntimeException("{$source} must implement ".ExportQuery::class);
            }
            $this->queryClass = $source;
        } else {
            $this->query = $source;
        }
    }

    /**
     * Create a new exporter.
     *
     * @param  EloquentBuilder<covariant Model>|QueryBuilder|class-string<ExportQuery>  $source
     */
    public static function make(EloquentBuilder|QueryBuilder|string $source): self
    {
        return new self($source);
    }

    /**
     * Set the columns to export.
     *
     * Accepts:
     * - Sequential: ['name', 'email'] (attribute name = header label)
     * - Associative: ['Full Name' => 'name', 'Email' => fn($model) => $model->email]
     * - Dot notation: ['Client' => 'client.name'] (resolves relationships via data_get)
     * - Transformer: ['Joined' => ['field' => 'created_at', 'transformer' => DateFormatTransformer::class]]
     *
     * @param  array<int|string, string|Closure|array<string, mixed>>  $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Set the row number where data starts (1-based, for template exports).
     */
    public function startingRow(int $row): self
    {
        $this->startingRow = $row;

        return $this;
    }

    /**
     * Set the sheet name.
     */
    public function sheet(string $name): self
    {
        $this->sheetName = $name;

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
     * Set a template file for the export.
     */
    public function template(string $templatePath): self
    {
        $this->templatePath = $templatePath;

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
     * Set the queue name for background processing.
     */
    public function onQueue(string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Use a custom writer implementation.
     */
    public function useWriter(WriterInterface $writer): self
    {
        $this->writer = $writer;

        return $this;
    }

    /**
     * Set column styles keyed by header label.
     *
     * Styles are arrays compatible with PhpSpreadsheet applyFromArray().
     * Special keys: 'width' (numeric) and 'autoSize' (bool) for column dimensions.
     *
     * @param  array<string, array<string, mixed>>  $styles
     */
    public function columnStyles(array $styles): self
    {
        $this->columnStyles = $styles;

        return $this;
    }

    /**
     * Set the header row style.
     *
     * Style array compatible with PhpSpreadsheet applyFromArray().
     *
     * @param  array<string, mixed>  $style
     */
    public function headerStyle(array $style): self
    {
        $this->headerStyle = $style;

        return $this;
    }

    /**
     * Set filters to pass to the ExportQuery constructor for background exports.
     *
     * Example:
     * ```php
     * ExcelExporter::make(YearlyReportExport::class)
     *     ->withFilters(['year' => 2025, 'status' => 'active'])
     *     ->dispatch('/path/to/output.xlsx');
     * ```
     *
     * The ExportQuery class should accept these in its constructor:
     * ```php
     * class YearlyReportExport implements ExportQuery
     * {
     *     public function __construct(
     *         public readonly ?int $year = null,
     *         public readonly ?string $status = null,
     *     ) {}
     *
     *     public function __invoke(): Builder
     *     {
     *         return Report::query()
     *             ->when($this->year, fn($q) => $q->whereYear('created_at', $this->year))
     *             ->when($this->status, fn($q) => $q->where('status', $this->status));
     *     }
     * }
     * ```
     *
     * @param  array<string, mixed>  $filters
     */
    public function withFilters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Export synchronously to a file.
     */
    public function export(string $outputPath): void
    {
        $query = $this->resolveQuery();
        $writer = $this->resolveWriter($outputPath);

        $outputDir = dirname($outputPath);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        try {
            $writer->open($outputPath, $this->sheetName);

            // Write header row if not using a template or if startingRow is 1
            if ($this->templatePath === null && $this->startingRow <= 1) {
                $writer->writeRow($this->getHeaderLabels());
            }

            foreach ($query->cursor() as $record) {
                $writer->writeRow($this->extractRowData($record));
            }
        } finally {
            $writer->close();
        }
    }

    /**
     * Export and return a download response.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('excel_export_').'_'.$filename;

        $this->export($tempPath);

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };

        return response()->download($tempPath, $filename, [
            'Content-Type' => $mimeType,
        ])->deleteFileAfterSend();
    }

    /**
     * Dispatch a background export job.
     */
    public function dispatch(string $outputPath): ExcelExport
    {
        if ($this->queryClass === null) {
            throw new RuntimeException(
                'Background export requires an ExportQuery class. Pass a class name to ExcelExporter::make() instead of a query builder.'
            );
        }

        $serializableColumns = $this->serializeColumns();

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => $outputPath,
            'user_id' => $this->userId,
            'reference' => $this->reference,
            'query_class' => $this->queryClass,
            'options' => [
                'columns' => $serializableColumns,
                'starting_row' => $this->startingRow,
                'template' => $this->templatePath,
                'sheet_name' => $this->sheetName,
                'column_styles' => $this->columnStyles,
                'header_style' => $this->headerStyle,
                'filters' => $this->filters,
            ],
        ]);

        $job = new ProcessExcelExport($task->id);

        if ($this->queue !== null) {
            $job->onQueue($this->queue);
        }

        dispatch($job);

        return $task;
    }

    /**
     * Resume a failed or stale export task.
     */
    public static function resume(ExcelExport $task): ExcelExport
    {
        if ($task->status === ExcelTaskStatus::Cancelled) {
            throw new RuntimeException('Cannot resume a cancelled export task.');
        }

        if ($task->status === ExcelTaskStatus::Completed) {
            throw new RuntimeException('Cannot resume a completed export task.');
        }

        // Handle stale processing tasks
        if ($task->status === ExcelTaskStatus::Processing && $task->isStale()) {
            $task->markFailed('Task was stale (no heartbeat). Marked as failed for resume.');
            $task->refresh();
        }

        if ($task->status !== ExcelTaskStatus::Failed) {
            throw new RuntimeException("Cannot resume export task with status: {$task->status->value}");
        }

        // Reset for fresh run (exports are idempotent)
        $task->update([
            'status' => ExcelTaskStatus::Pending,
            'processed_rows' => 0,
            'error_message' => null,
        ]);

        dispatch(new ProcessExcelExport($task->id));

        return $task->refresh();
    }

    /**
     * Resolve the query builder from the source.
     *
     * @return EloquentBuilder<covariant Model>|QueryBuilder
     */
    private function resolveQuery(): EloquentBuilder|QueryBuilder
    {
        if ($this->query !== null) {
            return $this->query;
        }

        if ($this->queryClass !== null) {
            $instance = new $this->queryClass;

            return $instance();
        }

        throw new RuntimeException('No query source provided.');
    }

    /**
     * Resolve the appropriate writer.
     */
    private function resolveWriter(string $outputPath): WriterInterface
    {
        // Custom writer takes precedence
        if ($this->writer !== null) {
            return $this->writer;
        }

        // Use StyledWriter when styles are set
        if (! empty($this->columnStyles) || ! empty($this->headerStyle)) {
            return $this->createStyledWriter($outputPath);
        }

        // Template without styles uses TemplateWriter
        if ($this->templatePath !== null) {
            return new TemplateWriter($this->templatePath, $this->startingRow);
        }

        // Default to SpoutWriter for speed
        return new SpoutWriter;
    }

    /**
     * Create and configure a StyledWriter.
     */
    private function createStyledWriter(string $outputPath): StyledWriter
    {
        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            throw new RuntimeException(
                'Column styles and header styles are not supported for CSV exports. Remove styling or use XLSX format.'
            );
        }

        $writer = new StyledWriter;
        $writer->setStartingRow($this->startingRow);
        $writer->setTemplatePath($this->templatePath);
        $writer->setColumnStyles($this->columnStyles);
        $writer->setHeaderStyle($this->headerStyle);
        $writer->setHeaderLabels($this->getHeaderLabels());

        return $writer;
    }

    /**
     * Get header labels from column configuration.
     *
     * @return array<int, string>
     */
    private function getHeaderLabels(): array
    {
        if (empty($this->columns)) {
            return [];
        }

        $headers = [];

        foreach ($this->columns as $key => $value) {
            if (is_int($key)) {
                // Sequential: attribute name is the header
                if (is_string($value)) {
                    $headers[] = $value;
                } elseif (is_array($value) && isset($value['field'])) {
                    $headers[] = $value['field'];
                } else {
                    $headers[] = "Column {$key}";
                }
            } else {
                // Associative: key is the header label
                $headers[] = $key;
            }
        }

        return $headers;
    }

    /**
     * Extract a row of data from a model/record using column config.
     *
     * @return array<int, mixed>
     */
    private function extractRowData(mixed $record): array
    {
        if (empty($this->columns)) {
            if ($record instanceof Model) {
                return array_values($record->toArray());
            }

            return array_values((array) $record);
        }

        $row = [];

        foreach ($this->columns as $value) {
            if ($value instanceof Closure) {
                $row[] = $value($record);
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

    /**
     * Serialize columns for storage (closures become null, arrays pass through).
     *
     * @return array<int|string, string|array<string, mixed>|null>
     */
    private function serializeColumns(): array
    {
        $serialized = [];

        foreach ($this->columns as $key => $value) {
            if ($value instanceof Closure) {
                $serialized[$key] = null;
            } else {
                $serialized[$key] = $value;
            }
        }

        return $serialized;
    }
}
