<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Synapse\Components\ExcelExportButton;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Models\ExcelExport;

uses(RefreshDatabase::class);

describe('ExcelExportButton Component', function () {
    it('defaults mode to async', function () {
        $component = new ExcelExportButton;

        expect($component->mode)->toBe('async');
    });

    it('defaults action to exportToExcel', function () {
        $component = new ExcelExportButton;

        expect($component->action)->toBe('exportToExcel');
    });

    it('applies default labels when none are given', function () {
        $component = new ExcelExportButton;

        expect($component->label)->toBe('Export to Excel');
        expect($component->exportingLabel)->toBe('Exporting...');
        expect($component->processingLabel)->toBe('Processing...');
        expect($component->downloadLabel)->toBe('Download');
        expect($component->failedLabel)->toBe('Export failed');
        expect($component->dismissLabel)->toBe('Remove');
    });

    it('falls back to default labels when explicitly passed an empty string', function () {
        $component = new ExcelExportButton(label: '', downloadLabel: '');

        expect($component->label)->toBe('Export to Excel');
        expect($component->downloadLabel)->toBe('Download');
    });

    it('keeps custom labels when given', function () {
        $component = new ExcelExportButton(
            label: 'Export',
            exportingLabel: 'Processing...',
            processingLabel: 'Processing...',
            downloadLabel: 'Download',
            failedLabel: 'Export failed',
            dismissLabel: 'Dismiss',
        );

        expect($component->label)->toBe('Export');
        expect($component->dismissLabel)->toBe('Dismiss');
    });

    it('stores the task, mode, and action props', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/out.xlsx',
            'total_rows' => 100,
            'processed_rows' => 40,
        ]);

        $component = new ExcelExportButton(
            mode: 'async',
            action: 'exportToExcel',
            task: $task,
            downloadAction: 'downloadExport',
            dismissAction: 'dismissExport',
        );

        expect($component->mode)->toBe('async');
        expect($component->task)->toBe($task);
        expect($component->downloadAction)->toBe('downloadExport');
        expect($component->dismissAction)->toBe('dismissExport');
    });

    it('renders the synapse::components.excel-export-button view', function () {
        $component = new ExcelExportButton;

        expect($component->render()->name())->toBe('synapse::components.excel-export-button');
    });
});
