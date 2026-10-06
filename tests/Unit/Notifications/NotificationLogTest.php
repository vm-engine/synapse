<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use VmEngine\Synapse\Notifications\NotificationLog;

it('logs a warning to the configured notifications log channel', function () {
    config(['synapps.apps.notifications.log_channel' => 'stack']);

    Log::shouldReceive('channel')->once()->with('stack')->andReturnSelf();
    Log::shouldReceive('warning')->once()->with('[synotif] role "ghost" does not exist; skipped.');

    NotificationLog::warning('role "ghost" does not exist; skipped.');
});

it('falls back to the default log channel when none is configured', function () {
    config(['synapps.apps.notifications.log_channel' => null]);
    config(['logging.default' => 'stack']);

    Log::shouldReceive('channel')->once()->with('stack')->andReturnSelf();
    Log::shouldReceive('info')->once()->with('[synotif] feature disabled, skipped.');

    NotificationLog::info('feature disabled, skipped.');
});
