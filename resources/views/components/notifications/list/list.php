<?php

use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Synapse\Notifications\Concerns\InteractsWithNotifications;
use VmEngine\Synapse\Notifications\NotificationGate;

new class extends Component
{
    use InteractsWithNotifications;
    use WithPagination;

    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = NotificationGate::enabled();
    }

    public function getItemsProperty()
    {
        $items = auth()->user()->notifications()
            ->latest()
            ->paginate(config('synapps.apps.notifications.modal_per_page', 15));

        $items->getCollection()->whereNull('read_at')->each->markAsRead();

        return $items;
    }

    public function markOne(string $id): void
    {
        $this->markNotificationRead($id);
    }

    public function markAll(): void
    {
        $this->markAllNotificationsRead();
    }

    public function render()
    {
        return view('synapse::components.notifications.list.list', ['items' => $this->enabled ? $this->items : null]);
    }
};
