<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Models\ExcelExport;
use VmEngine\Synapse\Traits\WithExcelExport;

uses(RefreshDatabase::class);

function excelExportTestComponent(): Component
{
    return new class extends Component
    {
        use WithExcelExport;

        public function findTask(string $reference): ?ExcelExport
        {
            return $this->excelExportTask($reference);
        }

        public function download(ExcelExport $task, string $filename, string $notFoundMessage = '')
        {
            return $this->downloadExcelExportFile($task, $filename, $notFoundMessage);
        }

        public function dismiss(ExcelExport $task): void
        {
            $this->dismissExcelExportTask($task);
        }

        public function render()
        {
            return '<div></div>';
        }
    };
}

describe('WithExcelExport trait', function () {
    it('finds a task by reference', function () {
        ExcelExport::create([
            'status' => ExcelTaskStatus::Completed,
            'output_path' => '/tmp/does-not-matter.xlsx',
            'reference' => 'my-ref',
        ]);

        $component = Livewire::test(excelExportTestComponent());

        $task = $component->instance()->findTask('my-ref');

        expect($task)->not->toBeNull();
        expect($task->reference)->toBe('my-ref');
    });

    it('returns null when no task matches the reference', function () {
        $component = Livewire::test(excelExportTestComponent());

        expect($component->instance()->findTask('missing-ref'))->toBeNull();
    });

    it('downloads a completed task with an existing file', function () {
        $path = tempnam(sys_get_temp_dir(), 'excel_export_test_');
        file_put_contents($path, 'fake xlsx contents');

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Completed,
            'output_path' => $path,
        ]);

        $component = Livewire::test(excelExportTestComponent());

        $response = $component->instance()->download($task, 'report.xlsx');

        expect($response)->toBeInstanceOf(BinaryFileResponse::class);

        @unlink($path);
    });

    it('dispatches a notify event with the default message when the file is missing', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Completed,
            'output_path' => '/tmp/does-not-exist-'.uniqid().'.xlsx',
        ]);

        Livewire::test(excelExportTestComponent())
            ->call('download', $task, 'report.xlsx')
            ->assertDispatched('notify', message: 'Export file not found.');
    });

    it('dispatches a notify event with a custom message when given one', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Completed,
            'output_path' => '/tmp/does-not-exist-'.uniqid().'.xlsx',
        ]);

        Livewire::test(excelExportTestComponent())
            ->call('download', $task, 'report.xlsx', 'Custom missing message.')
            ->assertDispatched('notify', message: 'Custom missing message.');
    });

    it('does not download a task that is not completed', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/does-not-matter.xlsx',
        ]);

        Livewire::test(excelExportTestComponent())
            ->call('download', $task, 'report.xlsx')
            ->assertDispatched('notify');
    });

    it('deletes the output file and the task record on dismiss', function () {
        $path = tempnam(sys_get_temp_dir(), 'excel_export_test_');
        file_put_contents($path, 'fake xlsx contents');

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Completed,
            'output_path' => $path,
        ]);

        $component = Livewire::test(excelExportTestComponent());
        $component->instance()->dismiss($task);

        expect(file_exists($path))->toBeFalse();
        expect(ExcelExport::find($task->id))->toBeNull();
    });
});
