<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Notifications;

use Illuminate\Support\Facades\Schema;

class NotificationGate
{
    /**
     * Whether in-app notifications are usable right now: the config flag is
     * on AND the stock `notifications` table actually exists. Memoized per
     * request via `once()` so repeated calls in one request don't re-query
     * the schema.
     */
    public static function enabled(): bool
    {
        return once(fn () => (bool) config('synapps.apps.notifications.enabled', false)
            && Schema::hasTable('notifications'));
    }
}
