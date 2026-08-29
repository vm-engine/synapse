<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Notifications\Concerns;

use Illuminate\Notifications\DatabaseNotification;

trait InteractsWithNotifications
{
    protected function markNotificationRead(string $id): void
    {
        auth()->user()?->notifications()->whereKey($id)->first()?->markAsRead();
    }

    protected function markAllNotificationsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function renderTitle(DatabaseNotification $notification): string
    {
        return __($notification->data['title'] ?? '', $notification->data['params'] ?? []);
    }

    public function renderMessage(DatabaseNotification $notification): string
    {
        return __($notification->data['message'] ?? '', $notification->data['params'] ?? []);
    }
}
