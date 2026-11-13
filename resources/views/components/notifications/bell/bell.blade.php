{{-- The root element must be unconditional: a template opening with @if puts Livewire's
     block-marker comment in front of the first tag, so wire:id lands on the next tag
     (the trigger button) and wire:poll / wire:click end up outside the component. --}}
<div
    @class(['synotif-bell' => $enabled])
    @if ($enabled) wire:poll.15s="refresh" x-data="{ open: false }" x-on:open-modal-notifications.window="open = false" @endif
>
    @if ($enabled)
        <button
            type="button"
            class="synotif-bell-trigger"
            x-on:click="open = !open"
            x-on:click.outside="open = false"
            aria-label="{{ __('Notifications') }}"
        >
            <i
                class="ph ph-bell"
                aria-hidden="true"
            ></i>
            @if ($this->unreadCount > 0)
                <span class="synotif-badge">{{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}</span>
            @endif
        </button>

        <div
            class="synotif-dropdown"
            x-show="open"
            x-cloak
            x-on:click.outside="open = false"
            wire:click="openPanel"
        >
            <div class="synotif-dropdown-header">
                <span>{{ __('Notifications') }}</span>
                @if ($this->unreadCount > 0)
                    <button
                        type="button"
                        class="synotif-mark-all"
                        wire:click="markAll"
                    >{{ __('Mark all as read') }}</button>
                @endif
            </div>

            <ul class="synotif-item-list">
                @forelse ($this->items as $notification)
                    <li class="synotif-item @if (in_array($notification->id, $visuallyUnreadIds)) synotif-item-unread @endif">
                        <button
                            type="button"
                            class="synotif-read-marker"
                            wire:click="markOne('{{ $notification->id }}')"
                            aria-label="{{ __('Mark as read') }}"
                        ></button>
                        <a
                            href="{{ $notification->data['url'] ?? '#' }}"
                            class="synotif-item-body"
                        >
                            @if (!empty($notification->data['icon']))
                                <i
                                    class="{{ $notification->data['icon'] }} synotif-item-icon"
                                    aria-hidden="true"
                                ></i>
                            @endif
                            <span class="synotif-item-title">{{ $this->renderTitle($notification) }}</span>
                            @if (!empty($notification->data['message']))
                                <span class="synotif-item-message">{{ $this->renderMessage($notification) }}</span>
                            @endif
                        </a>
                    </li>
                @empty
                    <li class="synotif-item-empty">{{ __('No notifications yet.') }}</li>
                @endforelse
            </ul>

            <div class="synotif-dropdown-footer">
                <button
                    type="button"
                    class="synotif-view-all"
                    x-on:click="$dispatch('open-modal-notifications')"
                >{{ __('View all') }}</button>
            </div>
        </div>
    @endif
</div>
