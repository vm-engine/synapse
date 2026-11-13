{{--
    Confirm Button Component — opens <x-synapse-confirm-dialog> for a Livewire method.

    Props:
    - method (string): Livewire method the dialog calls on confirm
    - label (string): Verb naming the action (tooltip / button text, and the dialog's confirm button)
    - title, message (string): Dialog text
    - token (string): A model's delete_token — use for deletes, never a raw id
    - param (string): Argument for any other action
    - icon (string): default 'ph ph-trash'; color (string): default 'danger'
    - component (string): Livewire component id the method lives on (optional)
    - labelled (bool): a text button instead of an icon with a tooltip
    - confirmText (string): override the dialog's confirm button text (defaults to label)

    Usage:
    <x-synapse-confirm-button method="delete" :token="$row->delete_token" :label="__('crm::labels.delete')"
        :title="__('crm::labels.confirm_delete_title')" :message="__('crm::labels.confirm_delete_message')" :component="$this->getId()" />
--}}
<button
    {{ $attributes->class([$labelled ? 'btn '.$color : 'btn-icon '.$color.' has-tooltip group']) }}
    type="button"
    data-title="{{ $title }}"
    data-message="{{ $message }}"
    data-confirm="{{ $confirmText ?? $label }}"
    data-cancel="{{ __('Cancel') }}"
    data-method="{{ $safeMethod }}"
    data-param="{{ $token ?? $param ?? '' }}"
    data-color="{{ $color }}"
    data-icon="{{ $icon }}"
    data-component="{{ $component }}"
    @if (! $labelled) aria-label="{{ $label }}" @endif
    @click="$dispatch('confirm-dialog', {
        title: $el.dataset.title,
        message: $el.dataset.message,
        confirmText: $el.dataset.confirm,
        cancelText: $el.dataset.cancel,
        confirmColor: $el.dataset.color,
        icon: $el.dataset.icon,
        wireMethod: $el.dataset.method,
        wireParams: $el.dataset.param === '' ? [] : [$el.dataset.param],
        wireComponent: $el.dataset.component
    })"
>
    <span class="{{ $icon }}" aria-hidden="true"></span>
    @if ($labelled)
        {{ $label }}
    @else
        <span class="tooltip">{{ $label }}</span>
    @endif
</button>
