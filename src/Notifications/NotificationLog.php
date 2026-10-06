<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Notifications;

use Illuminate\Support\Facades\Log;

class NotificationLog
{
    public static function warning(string $message): void
    {
        self::write('warning', $message);
    }

    public static function info(string $message): void
    {
        self::write('info', $message);
    }

    protected static function write(string $level, string $message): void
    {
        $channel = config('synapps.apps.notifications.log_channel') ?: config('logging.default');

        Log::channel($channel)->{$level}('[synotif] '.$message);
    }
}
