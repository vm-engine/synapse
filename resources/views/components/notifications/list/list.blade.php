{{-- Unconditional root: see the note in bell.blade.php. --}}
<div @class(['synotif-modal-list' => $enabled])>
    @if ($enabled)
        <div class="synotif-dropdown-header">
            <span>{{ __('All notifications') }}</span>
            <button
                type="button"
                class="synotif-mark-all"
                wire:click="markAll"
            >{{ __('Mark all as read') }}</button>
        </div>

        <ul class="synotif-item-list">
            @forelse ($items as $notification)
                <li class="synotif-item @if ($notification->read_at === null) synotif-item-unread @endif">
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

        <div class="synotif-modal-pagination">
            {{ $items->links() }}
        </div>
    @endif
</div>
