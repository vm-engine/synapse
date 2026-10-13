@if ($enabled())
    <div
        wire:ignore
        x-data="synapseRte({{ json_encode($config()) }})"
        {{ $attributes }}
    >
        <textarea
            class="form-input"
            id="{{ $inputId() }}"
            x-ref="textarea"
            name="{{ $field }}"
            rows="10"
        >{{ $value }}</textarea>
    </div>
@else
    <div {{ $attributes }}>
        <p class="form-error-message" role="alert">{{ $disabledMessage() }}</p>
        <textarea
            class="form-input"
            id="{{ $inputId() }}"
            name="{{ $field }}"
            rows="10"
            wire:model="{{ $field }}"
        >{{ $value }}</textarea>
    </div>
@endif
