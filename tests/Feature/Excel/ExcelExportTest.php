<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Models\ExcelExport;
use VmEngine\Synapse\Services\Excel\ExcelExporter;

uses(RefreshDatabase::class);

describe('ExcelExport Feature', function () {
    it('creates an export task record', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => '/tmp/output.xlsx',
            'user_id' => 1,
            'query_class' => 'App\\Exports\\UsersExport',
            'options' => ['columns' => ['name', 'email'], 'starting_row' => 1],
        ]);

        expect($task->id)->toBeGreaterThan(0);
        expect($task->status)->toBe(ExcelTaskStatus::Pending);
        expect($task->output_path)->toBe('/tmp/output.xlsx');
        expect($task->user_id)->toBe(1);
        expect($task->query_class)->toBe('App\\Exports\\UsersExport');
    });

    it('marks a task as completed', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/output.xlsx',
        ]);

        $task->markCompleted();
        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Completed);
        expect($task->completed_at)->not->toBeNull();
    });

    it('marks a task as failed', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/output.xlsx',
        ]);

        $task->markFailed('Disk full');
        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Failed);
        expect($task->error_message)->toBe('Disk full');
    });

    it('detects stale tasks', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/output.xlsx',
            'started_at' => now()->subHours(2),
            'last_heartbeat_at' => now()->subHours(2),
        ]);

        expect($task->isStale(3600))->toBeTrue();
    });

    it('does not detect recent tasks as stale', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/output.xlsx',
            'started_at' => now(),
            'last_heartbeat_at' => now(),
        ]);

        expect($task->isStale(3600))->toBeFalse();
    });

    it('updates heartbeat', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Processing,
            'output_path' => '/tmp/output.xlsx',
        ]);

        $task->heartbeat();
        $task->refresh();

        expect($task->last_heartbeat_at)->not->toBeNull();
    });

    it('throws when resuming a completed task', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Completed,
            'output_path' => '/tmp/output.xlsx',
        ]);

        ExcelExporter::resume($task);
    })->throws(RuntimeException::class, 'Cannot resume a completed');

    it('throws when resuming a cancelled task', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Cancelled,
            'output_path' => '/tmp/output.xlsx',
        ]);

        ExcelExporter::resume($task);
    })->throws(RuntimeException::class, 'Cannot resume a cancelled');

    it('casts status as ExcelTaskStatus enum', function () {
        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => '/tmp/output.xlsx',
        ]);

        $task->refresh();
        expect($task->status)->toBeInstanceOf(ExcelTaskStatus::class);
    });

    it('casts options as array', function () {
        $options = [
            'columns' => ['Name' => 'name', 'Email' => 'email'],
            'starting_row' => 1,
            'template' => null,
            'sheet_name' => 'Users',
        ];

        $task = ExcelExport::create([
            'status' => ExcelTaskStatus::Pending,
            'output_path' => '/tmp/output.xlsx',
            'options' => $options,
        ]);

        $task->refresh();
        expect($task->options)->toBeArray();
        expect($task->options['sheet_name'])->toBe('Users');
    });
});
