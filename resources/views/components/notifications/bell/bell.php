<?php

use Livewire\Component;
use VmEngine\Synapse\Notifications\Concerns\InteractsWithNotifications;
use VmEngine\Synapse\Notifications\NotificationGate;

new class extends Component
{
    use InteractsWithNotifications;

    public bool $enabled = false;

    public array $visuallyUnreadIds = [];

    public string $lastCheckedAt;

    public function mount(): void
    {
        $this->enabled = NotificationGate::enabled();
        $this->lastCheckedAt = now()->toDateTimeString();
    }

    public function getUnreadCountProperty(): int
    {
        if (! $this->enabled) {
            return 0;
        }

        return auth()->user()->unreadNotifications()->count();
    }

    public function getItemsProperty()
    {
        if (! $this->enabled) {
            return collect();
        }

        return auth()->user()->notifications()
            ->latest()
            ->take(config('synapps.apps.notifications.dropdown_limit', 5))
            ->get();
    }

    public function openPanel(): void
    {
        $items = $this->items;

        $this->visuallyUnreadIds = $items->whereNull('read_at')->pluck('id')->all();

        $items->whereNull('read_at')->each->markAsRead();
    }

    public function markOne(string $id): void
    {
        $this->markNotificationRead($id);
        $this->visuallyUnreadIds = array_values(array_diff($this->visuallyUnreadIds, [$id]));
    }

    public function markAll(): void
    {
        $this->markAllNotificationsRead();
        $this->visuallyUnreadIds = [];
    }

    public function refresh(): void
    {
        if (! $this->enabled) {
            return;
        }

        // ponytail: second-resolution cursor (>=, not >) — a notification created in
        // the same second as the last check can toastr twice in rapid succession.
        // Given the 15s poll interval this is a non-issue in practice; upgrade to a
        // last-seen-id cursor if sub-second duplicate toasts ever become a problem.
        $new = auth()->user()->notifications()
            ->where('created_at', '>=', $this->lastCheckedAt)
            ->get();

        foreach ($new as $notification) {
            if (($notification->data['toastr'] ?? false) === true) {
                $this->dispatch(
                    'notify',
                    variant: 'info',
                    title: $this->renderTitle($notification),
                    message: $this->renderMessage($notification),
                );
            }
        }

        $this->lastCheckedAt = now()->toDateTimeString();
    }
};
