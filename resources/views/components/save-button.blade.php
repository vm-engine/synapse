{{--
    Save Button Component — primary button with a busy state.

    Props:
    - action (string): Livewire method (default 'save')
    - label (string): default __('Save')
    - icon (string): default 'ph ph-floppy-disk'
    - target (string): extra wire:target, e.g. a file upload property
    - submit (bool): a form submit button (no wire:click)

    Usage: <x-synapse-save-button action="save" />
--}}
<button
    {{ $attributes->class(['btn primary']) }}
    @if ($submit) type="submit" @else type="button" wire:click="{{ $safeAction }}" @endif
    wire:loading.attr="disabled"
    wire:target="{{ $loadingTarget() }}"
>
    <span class="{{ $icon }}" aria-hidden="true" wire:loading.remove wire:target="{{ $safeAction }}"></span>
    <span class="ph ph-spinner syn-spin" aria-hidden="true" wire:loading wire:target="{{ $safeAction }}"></span>
    <span wire:loading.remove wire:target="{{ $safeAction }}">{{ $label ?? __('Save') }}</span>
    <span wire:loading wire:target="{{ $safeAction }}">{{ __('Saving…') }}</span>
</button>
