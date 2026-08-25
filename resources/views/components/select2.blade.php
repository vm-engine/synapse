@props([
    'name' => '',
    'options' => [],
    'model' => null,
    'multiple' => false,
    'placeholder' => 'Select option',
    'config' => '{}',
    'remoteDataMethod' => null,
])

@php
    $selectedValues = is_array($model) ? $model : ($model ? [$model] : []);
    $componentId = 'select2-' . $name . '-' . uniqid();
@endphp

@assets
    <script
        src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"
    ></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link
        href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
        rel="stylesheet"
    >
@endassets

<div wire:ignore>
    <select
        id="{{ $componentId }}"
        name="{{ $name }}"
        {{ $multiple ? 'multiple' : '' }}
        {{ $attributes->except(['wire:model', 'wire:model.live'])->merge(['class' => 'w-full']) }}
    >
        @if (!$multiple && !empty($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $key => $value)
            <option
                value="{{ $key }}"
                {{ in_array((string) $key, array_map('strval', $selectedValues)) ? 'selected' : '' }}
            >
                {{ $value }}
            </option>
        @endforeach
    </select>
</div>

@php
    $wireModelAttr = collect($attributes->getAttributes())
        ->keys()
        ->first(fn($key) => str_starts_with($key, 'wire:model'));
    $isLive = $wireModelAttr && str_contains($wireModelAttr, '.live');
@endphp
@script
    <script nonce="{{ csp_nonce() }}">
        $(document).ready(function() {
            const componentId = '{{ $componentId }}';
            const $select = $('#' + componentId);
            const isMultiple = {{ $multiple ? 'true' : 'false' }};
            const isLiveUpdate = {{ $isLive ? 'true' : 'false' }};

            // Select2 options
            const select2Options = {{ $config }};
            console.log(select2Options);


            // Initialize Select2
            $select.select2(select2Options);

            // Fix body scroll lock
            // $select.on('select2:open', function(e) {
            //     $('body').css('overflow', '');
            // });

            // $select.on('select2:close', function(e) {
            //     $('body').css('overflow', '');
            // });

            // $select.on('select2:select', function(e) {
            //     $('body').css('overflow', '');
            //     if (!isMultiple) {
            //         $select.select2('close');
            //     }
            // });

            // $select.on('select2:unselect', function(e) {
            //     $('body').css('overflow', '');
            //     setTimeout(() => $select.select2('close'), 1);
            // });

            // // Allow clicking outside to close dropdown
            // $(document).on('click', function(e) {
            //     if (!$(e.target).closest('.select2-container').length &&
            //         !$(e.target).closest('#' + componentId).length) {
            //         $select.select2('close');
            //         $('body').css('overflow', '');
            //     }
            // });

            // Sync Select2 changes to Livewire (only if wire:model.live)
            $select.on('change', function(e) {
                // if (isLiveUpdate) {
                const value = $(e.target).val();
                const finalValue = isMultiple ? (value || []) : (value || null);
                $wire.$set('{{ $attributes->wire('model')->value() }}', finalValue, isLiveUpdate);
                // }
            });
        });
    </script>
@endscript
