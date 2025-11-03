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
    <style>
        /* Multiple Select */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            min-height: 2.75rem;
            padding: 0.375rem 0.5rem;
        }

        .dark .select2-container--default .select2-selection--multiple {
            background-color: rgb(17 24 39);
            border-color: rgb(55 65 81);
        }

        /* Single Select */
        .select2-container--default .select2-selection--single {
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            height: 2.75rem;
            display: flex;
            align-items: center;
        }

        .dark .select2-container--default .select2-selection--single {
            background-color: rgb(17 24 39);
            border-color: rgb(55 65 81);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-left: 0.75rem;
            padding-right: 2rem;
            line-height: 2.75rem;
            color: rgb(17 24 39);
        }

        .dark .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: rgb(229 231 235);
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: rgb(107 114 128);
        }

        .dark .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: rgb(156 163 175);
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.75rem;
            right: 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: rgb(107 114 128) transparent transparent transparent;
            border-width: 6px 6px 0 6px;
            margin-left: -6px;
            margin-top: -3px;
            left: 50%;
            top: 50%;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent rgb(107 114 128) transparent;
            border-width: 0 6px 6px 6px;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 0;
            color: rgb(107 114 128);
            font-size: 1.5rem;
            height: auto;
            padding: 0.25rem 0.5rem;
            background-color: lightgray;
            border-radius: 0.1rem;
        }

        .dark .select2-container--default .select2-selection--single .select2-selection__clear {
            color: rgb(156 163 175);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: rgb(219 234 254);
            border: none;
            border-radius: 9999px;
            padding: 0.125rem 0.625rem;
            color: rgb(29 78 216);
        }

        .dark .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: rgba(29, 78, 216, 0.3);
            color: rgb(147 197 253);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
            padding-left: 0.7rem !important;
        }

        .select2-container--default .select2-results__option--highlighted {
            background-color: rgb(219 234 254);
            color: rgb(29 78 216);
        }

        .dark .select2-dropdown {
            background-color: rgb(17 24 39);
            border-color: rgb(55 65 81);
        }

        .dark .select2-container--default .select2-results__option {
            color: rgb(229 231 235);
        }

        .dark .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: rgb(31 41 55);
            border-color: rgb(55 65 81);
            color: rgb(229 231 235);
        }

        .select2-container {
            width: 100% !important;
        }
    </style>
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
    <script>
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
