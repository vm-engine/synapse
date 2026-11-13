{{--
    Form Action Bar Component — sticky bar for long dedicated-page forms.

    Props:
    - action (string): save method (default 'save')
    - cancelUrl (string): renders a Cancel link when set
    - saveLabel, saveTarget (string): passed to <x-synapse-save-button>
    - hint (string|false): required-field hint (default __('Required field')); false hides it

    Slot: extra buttons, rendered before the save button.

    Usage: <x-synapse-form-action-bar action="save" :cancel-url="backend_route('crm.clients.index')" />
--}}
<div {{ $attributes->class(['syn-form-action-bar']) }}>
    @if ($hint !== false && $hint !== '')
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <span class="text-red-500" aria-hidden="true">*</span> {{ $hint ?? __('Required field') }}
        </p>
    @else
        <span></span>
    @endif
    <div class="flex items-center gap-3">
        @if ($cancelUrl)
            <a class="btn" href="{{ $cancelUrl }}" wire:navigate>
                <span class="ph ph-x" aria-hidden="true"></span> {{ __('Cancel') }}
            </a>
        @endif
        {{ $slot }}
        <x-synapse-save-button :action="$action" :label="$saveLabel" :target="$saveTarget" />
    </div>
</div>
