<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Jobs\ProcessExcelImport;
use VmEngine\Synapse\Models\ExcelImport;
use VmEngine\Synapse\Services\Excel\Contracts\ProcessesImport;
use VmEngine\Synapse\Services\Excel\Contracts\ReceivesImportTask;

uses(RefreshDatabase::class);

it('hands the import task to processors that ask for it, before the first chunk', function () {
    TaskAwareProcessor::$seen = [];

    $task = ExcelImport::create([
        'status' => ExcelTaskStatus::Pending,
        'file_path' => __DIR__.'/../../fixtures/sample.csv',
        'processor_class' => TaskAwareProcessor::class,
        'options' => ['mapping' => ['name', 'email', 'phone'], 'starting_row' => 2, 'chunk_size' => 2],
    ]);

    (new ProcessExcelImport($task->id))->handle();

    expect(TaskAwareProcessor::$seen)->toBe([[$task->id, 0, 2], [$task->id, 1, 1]])
        ->and($task->refresh()->status)->toBe(ExcelTaskStatus::Completed);
});

class TaskAwareProcessor implements ProcessesImport, ReceivesImportTask
{
    /** @var list<array{int, int, int}> */
    public static array $seen = [];

    private ?ExcelImport $task = null;

    public function setImportTask(ExcelImport $task): void
    {
        $this->task = $task;
    }

    public function process(Collection $rows, int $chunkIndex): void
    {
        self::$seen[] = [(int) $this->task?->id, $chunkIndex, $rows->count()];
    }
}
