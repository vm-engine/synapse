<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Synapse\Enums\ExcelTaskStatus;
use VmEngine\Synapse\Models\ExcelImport;
use VmEngine\Synapse\Services\Excel\ExcelImporter;

uses(RefreshDatabase::class);

describe('ExcelImport Feature', function () {
    it('creates an import task record', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Pending,
            'file_path' => '/tmp/test.csv',
            'user_id' => 1,
            'options' => ['mapping' => ['name', 'email'], 'starting_row' => 2],
        ]);

        expect($task->id)->toBeGreaterThan(0);
        expect($task->status)->toBe(ExcelTaskStatus::Pending);
        expect($task->file_path)->toBe('/tmp/test.csv');
        expect($task->user_id)->toBe(1);
        expect($task->options)->toBeArray();
        expect($task->options['mapping'])->toBe(['name', 'email']);
    });

    it('marks a task as completed', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => '/tmp/test.csv',
        ]);

        $task->markCompleted();
        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Completed);
        expect($task->completed_at)->not->toBeNull();
    });

    it('marks a task as failed with error message', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => '/tmp/test.csv',
            'processed_rows' => 50,
        ]);

        $task->markFailed('Database error at row 51', 51);
        $task->refresh();

        expect($task->status)->toBe(ExcelTaskStatus::Failed);
        expect($task->error_message)->toBe('Database error at row 51');
        expect($task->failed_at_row)->toBe(51);
    });

    it('detects stale tasks', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => '/tmp/test.csv',
            'started_at' => now()->subHours(2),
            'last_heartbeat_at' => now()->subHours(2),
        ]);

        expect($task->isStale(3600))->toBeTrue();
    });

    it('does not detect recent tasks as stale', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => '/tmp/test.csv',
            'started_at' => now(),
            'last_heartbeat_at' => now(),
        ]);

        expect($task->isStale(3600))->toBeFalse();
    });

    it('does not detect non-processing tasks as stale', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Failed,
            'file_path' => '/tmp/test.csv',
            'started_at' => now()->subHours(2),
            'last_heartbeat_at' => now()->subHours(2),
        ]);

        expect($task->isStale(3600))->toBeFalse();
    });

    it('updates heartbeat', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Processing,
            'file_path' => '/tmp/test.csv',
        ]);

        expect($task->last_heartbeat_at)->toBeNull();

        $task->heartbeat();
        $task->refresh();

        expect($task->last_heartbeat_at)->not->toBeNull();
    });

    it('throws when resuming a completed task', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Completed,
            'file_path' => '/tmp/test.csv',
        ]);

        ExcelImporter::resume($task);
    })->throws(RuntimeException::class, 'Cannot resume a completed');

    it('throws when resuming a cancelled task', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Cancelled,
            'file_path' => '/tmp/test.csv',
        ]);

        ExcelImporter::resume($task);
    })->throws(RuntimeException::class, 'Cannot resume a cancelled');

    it('casts status as ExcelTaskStatus enum', function () {
        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Pending,
            'file_path' => '/tmp/test.csv',
        ]);

        $task->refresh();
        expect($task->status)->toBeInstanceOf(ExcelTaskStatus::class);
        expect($task->status)->toBe(ExcelTaskStatus::Pending);
    });

    it('casts options as array', function () {
        $options = ['mapping' => ['name', 'email'], 'chunk_size' => 500];

        $task = ExcelImport::create([
            'status' => ExcelTaskStatus::Pending,
            'file_path' => '/tmp/test.csv',
            'options' => $options,
        ]);

        $task->refresh();
        expect($task->options)->toBeArray();
        expect($task->options['chunk_size'])->toBe(500);
    });
});
