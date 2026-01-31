<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Events\ExcelImportCompleted;
use VmEngine\Synapse\Events\ExcelImportFailed;
use VmEngine\Synapse\Jobs\ProcessExcelExport;
use VmEngine\Synapse\Jobs\ProcessExcelImport;
use VmEngine\Synapse\Models\ExcelExport;
use VmEngine\Synapse\Models\ExcelImport;
use VmEngine\Synapse\Services\Excel\Contracts\ExportQuery;
use VmEngine\Synapse\Services\Excel\Contracts\ProcessesImport;
use VmEngine\Synapse\Services\Excel\ExcelExporter;
use VmEngine\Synapse\Services\Excel\ExcelImporter;
use VmEngine\Synapse\Services\Excel\Transformers\BooleanTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\UpperCaseTransformer;

uses(RefreshDatabase::class);

describe('ExcelImporter dispatch', function () {
    it('dispatches import job to queue', function () {
        Queue::fake();

        $filePath = __DIR__.'/../../fixtures/sample.csv';

        $task = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->mapColumns(['name', 'email', 'phone'])
            ->forUser(1)
            ->onQueue('imports')
            ->dispatch(TestImportProcessor::class, chunkSize: 500);

        expect($task)->toBeInstanceOf(ExcelImport::class);
        expect($task->status)->toBe(ExcelTaskStatus::Pending);
        expect($task->file_path)->toBe($filePath);
        expect($task->user_id)->toBe(1);
        expect($task->processor_class)->toBe(TestImportProcessor::class);
        expect($task->options['chunk_size'])->toBe(500);
        expect($task->options['starting_row'])->toBe(2);

        Queue::assertPushed(ProcessExcelImport::class, function ($job) use ($task) {
            return $job->taskId === $task->id;
        });
    });

    it('throws when processor class does not implement interface', function () {
        ExcelImporter::make('/tmp/test.csv')
            ->dispatch(\stdClass::class);
    })->throws(RuntimeException::class, 'must implement');

    it('processes import job synchronously', function () {
        Event::fake([ExcelImportCompleted::class]);

        $filePath = __DIR__.'/../../fixtures/sample.csv';

        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Pending,
            'file_path' => $filePath,
            'processor_class' => TestImportProcessor::class,
            'options' => [
                'mapping' => ['name', 'email', 'phone'],
                'starting_row' => 2,
                'chunk_size' => 1000,
            ],
        ]);

        $job = new ProcessExcelImport($task->id);
        $job->handle();

        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Completed);
        expect($task->total_rows)->toBe(3);
        expect($task->completed_at)->not->toBeNull();

        Event::assertDispatched(ExcelImportCompleted::class, function ($event) use ($task) {
            return $event->task->id === $task->id;
        });
    });

    it('handles failed import job', function () {
        Event::fake([ExcelImportFailed::class]);

        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Pending,
            'file_path' => '/nonexistent/file.csv',
            'processor_class' => TestImportProcessor::class,
            'options' => [
                'mapping' => ['name'],
                'starting_row' => 2,
                'chunk_size' => 1000,
            ],
        ]);

        $job = new ProcessExcelImport($task->id);

        try {
            $job->handle();
        } catch (\Throwable) {
            // Expected
        }

        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Failed);
        expect($task->error_message)->not->toBeNull();

        Event::assertDispatched(ExcelImportFailed::class);
    });

    it('skips cancelled import job', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Cancelled,
            'file_path' => '/tmp/test.csv',
            'processor_class' => TestImportProcessor::class,
            'options' => [
                'mapping' => ['name'],
                'starting_row' => 1,
                'chunk_size' => 1000,
            ],
        ]);

        $job = new ProcessExcelImport($task->id);
        $job->handle();

        $task->refresh();
        expect($task->status)->toBe(ExcelTaskStatus::Cancelled);
    });
});

describe('ExcelExporter dispatch', function () {
    it('dispatches export job to queue', function () {
        Queue::fake();

        $task = ExcelExporter::make(TestExportQuery::class)
            ->columns(['Name' => 'name', 'Email' => 'email'])
            ->forUser(1)
            ->onQueue('exports')
            ->dispatch('/tmp/output.xlsx');

        expect($task)->toBeInstanceOf(ExcelExport::class);
        expect($task->status)->toBe(ExcelTaskStatus::Pending);
        expect($task->output_path)->toBe('/tmp/output.xlsx');
        expect($task->user_id)->toBe(1);
        expect($task->query_class)->toBe(TestExportQuery::class);

        Queue::assertPushed(ProcessExcelExport::class, function ($job) use ($task) {
            return $job->taskId === $task->id;
        });
    });

    it('resumes a failed import by dispatching new job', function () {
        Queue::fake();

        $filePath = __DIR__.'/../../fixtures/sample.csv';

        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Failed,
            'file_path' => $filePath,
            'processor_class' => TestImportProcessor::class,
            'processed_rows' => 10,
            'failed_at_row' => 10,
            'error_message' => 'Previous error',
            'options' => [
                'mapping' => ['name', 'email'],
                'starting_row' => 2,
                'chunk_size' => 1000,
            ],
        ]);

        $resumed = ExcelImporter::resume($task);

        expect($resumed->status)->toBe(ExcelTaskStatus::Pending);
        Queue::assertPushed(ProcessExcelImport::class);
    });

    it('resumes a failed export by dispatching new job', function () {
        Queue::fake();

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Failed,
            'output_path' => '/tmp/output.xlsx',
            'query_class' => TestExportQuery::class,
            'error_message' => 'Previous error',
            'processed_rows' => 50,
            'options' => [
                'columns' => ['name', 'email'],
                'starting_row' => 1,
            ],
        ]);

        $resumed = ExcelExporter::resume($task);

        expect($resumed->status)->toBe(ExcelTaskStatus::Pending);
        expect($resumed->processed_rows)->toBe(0); // Reset for idempotent re-run
        expect($resumed->error_message)->toBeNull();
        Queue::assertPushed(ProcessExcelExport::class);
    });

    it('resumes a stale import task', function () {
        Queue::fake();

        $filePath = __DIR__.'/../../fixtures/sample.csv';

        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => $filePath,
            'processor_class' => TestImportProcessor::class,
            'processed_rows' => 10,
            'started_at' => now()->subHours(2),
            'last_heartbeat_at' => now()->subHours(2),
            'options' => [
                'mapping' => ['name', 'email'],
                'starting_row' => 2,
                'chunk_size' => 1000,
            ],
        ]);

        $resumed = ExcelImporter::resume($task);

        expect($resumed->status)->toBe(ExcelTaskStatus::Pending);
        Queue::assertPushed(ProcessExcelImport::class);
    });
});

describe('Excel Cleanup Command', function () {
    it('marks stale import tasks as failed', function () {
        $staleTask = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => '/tmp/test.csv',
            'started_at' => now()->subHours(2),
            'last_heartbeat_at' => now()->subHours(2),
        ]);

        $recentTask = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => '/tmp/test2.csv',
            'started_at' => now(),
            'last_heartbeat_at' => now(),
        ]);

        $this->artisan('synapse:excel-cleanup', ['--timeout' => 3600])
            ->assertSuccessful();

        $staleTask->refresh();
        $recentTask->refresh();

        expect($staleTask->status)->toBe(ExcelTaskStatus::Failed);
        expect($recentTask->status)->toBe(ExcelTaskStatus::Processing);
    });

    it('marks stale export tasks as failed', function () {
        $staleTask = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/output.xlsx',
            'started_at' => now()->subHours(2),
            'last_heartbeat_at' => now()->subHours(2),
        ]);

        $this->artisan('synapse:excel-cleanup', ['--timeout' => 3600])
            ->assertSuccessful();

        $staleTask->refresh();
        expect($staleTask->status)->toBe(ExcelTaskStatus::Failed);
    });

    it('reports no stale tasks when none exist', function () {
        $this->artisan('synapse:excel-cleanup')
            ->expectsOutputToContain('No stale tasks found')
            ->assertSuccessful();
    });
});

// Test helpers

class TestImportProcessor implements ProcessesImport
{
    /** @var array<int, array<string, mixed>> */
    public static array $processedRows = [];

    public function process(Collection $rows, int $chunkIndex): void
    {
        self::$processedRows = array_merge(self::$processedRows, $rows->toArray());
    }
}

class TestExportQuery implements ExportQuery
{
    public function __invoke(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
    {
        return \VmEngine\Synapse\Models\Config::query();
    }
}

class FilteredExportQuery implements ExportQuery
{
    public function __construct(
        public readonly ?string $group = null,
        public readonly ?string $keyPrefix = null,
    ) {}

    public function __invoke(): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
    {
        return \VmEngine\Synapse\Models\Config::query()
            ->when($this->group, fn ($q) => $q->where('group', $this->group))
            ->when($this->keyPrefix, fn ($q) => $q->where('key', 'like', $this->keyPrefix.'%'));
    }
}

describe('ExcelExporter with styles dispatch', function () {
    it('includes column_styles and header_style in serialized options', function () {
        Queue::fake();

        $task = ExcelExporter::make(TestExportQuery::class)
            ->columns(['Label' => 'key', 'Value' => 'value'])
            ->columnStyles([
                'Label' => ['width' => 30, 'font' => ['bold' => true]],
                'Value' => ['width' => 50],
            ])
            ->headerStyle([
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            ])
            ->forUser(1)
            ->dispatch('/tmp/styled_output.xlsx');

        expect($task)->toBeInstanceOf(ExcelExport::class);
        expect($task->options['column_styles'])->toBe([
            'Label' => ['width' => 30, 'font' => ['bold' => true]],
            'Value' => ['width' => 50],
        ]);
        expect($task->options['header_style'])->toBe([
            'font' => ['bold' => true],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
        ]);

        Queue::assertPushed(ProcessExcelExport::class);
    });

    it('processes background export with styles', function () {
        // Create some test data in configs table
        \VmEngine\Synapse\Models\Config::create([
            'group' => 'test',
            'key' => 'setting1',
            'value' => 'value1',
            'label' => 'Test Setting 1',
        ]);
        \VmEngine\Synapse\Models\Config::create([
            'group' => 'test',
            'key' => 'setting2',
            'value' => 'value2',
            'label' => 'Test Setting 2',
        ]);

        $outputDir = sys_get_temp_dir().'/synapse_job_style_test_'.uniqid();
        mkdir($outputDir, 0755, true);
        $outputPath = $outputDir.'/styled_export.xlsx';

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => $outputPath,
            'query_class' => TestExportQuery::class,
            'options' => [
                'columns' => ['Label' => 'key', 'Value' => 'value'],
                'starting_row' => 1,
                'column_styles' => [
                    'Label' => ['width' => 25],
                    'Value' => ['font' => ['bold' => true]],
                ],
                'header_style' => [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '333333']],
                ],
            ],
        ]);

        $job = new ProcessExcelExport($task->id);
        $job->handle();

        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Completed);
        expect(file_exists($outputPath))->toBeTrue();

        // Verify styles were applied
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Column widths
        expect($sheet->getColumnDimension('A')->getWidth())->toBe(25.0);

        // Header style
        expect($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('333333');

        // Data cell style (Value column should be bold)
        expect($sheet->getStyle('B2')->getFont()->getBold())->toBeTrue();

        // Freeze pane
        expect($sheet->getFreezePane())->toBe('A2');

        $spreadsheet->disconnectWorksheets();

        // Cleanup
        unlink($outputPath);
        rmdir($outputDir);
    });
});

describe('ExcelExporter with reference dispatch', function () {
    it('includes reference when dispatching export', function () {
        Queue::fake();

        $task = ExcelExporter::make(TestExportQuery::class)
            ->columns(['Label' => 'key', 'Value' => 'value'])
            ->reference('my-export-ref-42')
            ->forUser(1)
            ->dispatch('/tmp/ref_output.xlsx');

        expect($task)->toBeInstanceOf(ExcelExport::class);
        expect($task->reference)->toBe('my-export-ref-42');

        Queue::assertPushed(ProcessExcelExport::class);
    });

    it('includes reference when dispatching import', function () {
        Queue::fake();

        $filePath = __DIR__.'/../../fixtures/sample.csv';

        $task = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->mapColumns(['name', 'email', 'phone'])
            ->reference('my-import-ref-99')
            ->forUser(1)
            ->dispatch(TestImportProcessor::class);

        expect($task)->toBeInstanceOf(ExcelImport::class);
        expect($task->reference)->toBe('my-import-ref-99');

        Queue::assertPushed(ProcessExcelImport::class);
    });
});

describe('ExcelExporter background with transformers', function () {
    it('processes background export with transformer columns', function () {
        \VmEngine\Synapse\Models\Config::create([
            'group' => 'test',
            'key' => 'setting1',
            'value' => 'hello world',
            'label' => 'Test Setting 1',
        ]);

        $outputDir = sys_get_temp_dir().'/synapse_job_transformer_test_'.uniqid();
        mkdir($outputDir, 0755, true);
        $outputPath = $outputDir.'/transformer_export.xlsx';

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => $outputPath,
            'query_class' => TestExportQuery::class,
            'options' => [
                'columns' => [
                    'Key' => [
                        'field' => 'key',
                        'transformer' => UpperCaseTransformer::class,
                    ],
                    'Value' => 'value',
                ],
                'starting_row' => 1,
            ],
        ]);

        $job = new ProcessExcelExport($task->id);
        $job->handle();

        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Completed);
        expect(file_exists($outputPath))->toBeTrue();

        $reader = new \VmEngine\Synapse\Services\Excel\Readers\SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        // Header + 1 data row
        expect($rows)->toHaveCount(2);
        expect($rows[0])->toBe(['Key', 'Value']);
        expect($rows[1][0])->toBe('SETTING1'); // Uppercased by transformer
        expect($rows[1][1])->toBe('hello world');

        unlink($outputPath);
        rmdir($outputDir);
    });

    it('processes background export with transformer config', function () {
        \VmEngine\Synapse\Models\Config::create([
            'group' => 'test',
            'key' => 'active_flag',
            'value' => '1',
            'label' => 'Active Flag',
        ]);

        $outputDir = sys_get_temp_dir().'/synapse_job_bool_test_'.uniqid();
        mkdir($outputDir, 0755, true);
        $outputPath = $outputDir.'/bool_export.xlsx';

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => $outputPath,
            'query_class' => TestExportQuery::class,
            'options' => [
                'columns' => [
                    'Key' => 'key',
                    'Active' => [
                        'field' => 'value',
                        'transformer' => BooleanTransformer::class,
                        'config' => ['trueLabel' => 'Enabled', 'falseLabel' => 'Disabled'],
                    ],
                ],
                'starting_row' => 1,
            ],
        ]);

        $job = new ProcessExcelExport($task->id);
        $job->handle();

        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Completed);

        $reader = new \VmEngine\Synapse\Services\Excel\Readers\SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows[1][1])->toBe('Enabled');

        unlink($outputPath);
        rmdir($outputDir);
    });
});

describe('ExcelExporter with filters dispatch', function () {
    it('includes filters in serialized options', function () {
        Queue::fake();

        $task = ExcelExporter::make(FilteredExportQuery::class)
            ->columns(['Label' => 'key', 'Value' => 'value'])
            ->withFilters(['group' => 'app', 'keyPrefix' => 'setting'])
            ->forUser(1)
            ->dispatch('/tmp/filtered_output.xlsx');

        expect($task)->toBeInstanceOf(ExcelExport::class);
        expect($task->options['filters'])->toBe([
            'group' => 'app',
            'keyPrefix' => 'setting',
        ]);

        Queue::assertPushed(ProcessExcelExport::class);
    });

    it('processes background export with filters', function () {
        // Create test data with different groups
        \VmEngine\Synapse\Models\Config::create([
            'group' => 'app',
            'key' => 'setting1',
            'value' => 'value1',
            'label' => 'App Setting 1',
        ]);
        \VmEngine\Synapse\Models\Config::create([
            'group' => 'app',
            'key' => 'setting2',
            'value' => 'value2',
            'label' => 'App Setting 2',
        ]);
        \VmEngine\Synapse\Models\Config::create([
            'group' => 'system',
            'key' => 'other',
            'value' => 'value3',
            'label' => 'System Setting',
        ]);

        $outputDir = sys_get_temp_dir().'/synapse_job_filter_test_'.uniqid();
        mkdir($outputDir, 0755, true);
        $outputPath = $outputDir.'/filtered_export.xlsx';

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => $outputPath,
            'query_class' => FilteredExportQuery::class,
            'options' => [
                'columns' => ['Label' => 'key', 'Value' => 'value'],
                'starting_row' => 1,
                'filters' => [
                    'group' => 'app',
                    'keyPrefix' => 'setting',
                ],
            ],
        ]);

        $job = new ProcessExcelExport($task->id);
        $job->handle();

        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Completed);
        expect(file_exists($outputPath))->toBeTrue();

        // Verify only filtered data was exported (2 rows + header)
        expect($task->total_rows)->toBe(2);
        expect($task->processed_rows)->toBe(2);

        // Read file to verify content
        $reader = new \VmEngine\Synapse\Services\Excel\Readers\SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        // Header + 2 data rows
        expect($rows)->toHaveCount(3);
        expect($rows[0])->toBe(['Label', 'Value']);
        expect($rows[1][0])->toBe('setting1');
        expect($rows[2][0])->toBe('setting2');

        // Cleanup
        unlink($outputPath);
        rmdir($outputDir);
    });
});
