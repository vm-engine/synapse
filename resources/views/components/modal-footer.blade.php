{{--
    Modal Footer Component — for a form rendered inside <x-synapse-modal>.

    Props:
    - modal (string): the modal's name (Cancel dispatches close-modal-{name})
    - action (string): save method (default 'save'); saveLabel (string)

    Slot: replaces the save button.

    Usage: <x-synapse-modal-footer modal="tax-rate-form" />
--}}
<div {{ $attributes->class(['syn-modal-footer']) }}>
    <button class="btn" type="button" @click="$dispatch('close-modal-{{ $name }}')">
        <span class="ph ph-x" aria-hidden="true"></span> {{ __('Cancel') }}
    </button>
    @if ($slot->isNotEmpty())
        {{ $slot }}
    @else
        <x-synapse-save-button :action="$action" :label="$saveLabel" />
    @endif
</div>
