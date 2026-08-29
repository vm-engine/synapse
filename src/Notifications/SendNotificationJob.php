<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Runs the same resolve+gate+send Notify::deliver() does, but on a queue
 * worker instead of inline in the request — for a large `toRoles()`
 * recipient list where even resolving+dispatching inline would be slow.
 *
 * If $notification carries its own Eloquent model properties (e.g. an
 * invoice), have that notification class `use SerializesModels;` too, same
 * as any other queued notification — this job only serializes $recipients
 * and the notification instance as a whole, not what's inside it.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  Collection<int, Model>  $recipients
     */
    public function __construct(
        public Collection $recipients,
        public object $notification,
    ) {}

    public function handle(): void
    {
        Notify::deliver($this->recipients, $this->notification);
    }
}
