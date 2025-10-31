@props([
    'name' => '',
    'options' => [],
    'model' => null,
    'multiple' => false,
    'searchable' => true,
    'ajax' => false,
    'ajaxUrl' => '',
    'placeholder' => 'Select option',
])

@php
    $selectedValues = is_array($model) ? $model : ($model ? [$model] : []);
    $componentId = 'select2-' . $name . '-' . uniqid();
@endphp

@once
    @push('scripts')
        <script
            src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"
        ></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @endpush

    @push('styles')
        <link
            href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
            rel="stylesheet"
        />
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
    @endpush
@endonce

<div
    x-data="{
        select2Instance: null,
        initAttempts: 0,
        maxAttempts: 10,
        init() {
            this.tryInitialize();
        },
        tryInitialize() {
            try {
                // Check if jQuery and Select2 are loaded
                if (typeof $ === 'undefined' || typeof $.fn === 'undefined' || typeof $.fn.select2 === 'undefined') {
                    // Retry initialization after a short delay
                    if (this.initAttempts < this.maxAttempts) {
                        this.initAttempts++;
                        setTimeout(() => this.tryInitialize(), 100);
                        return;
                    } else {
                        console.error('Select2: jQuery or Select2 library not loaded after', this.maxAttempts, 'attempts');
                        return;
                    }
                }

                const $select = $('#{{ $componentId }}');

                if ($select.length === 0) {
                    console.error('Select2: Element #{{ $componentId }} not found');
                    return;
                }

                // Initialize Select2
                const isMultiple = {{ $multiple ? 'true' : 'false' }};
                @php
                    $wireModelAttr = collect($attributes->getAttributes())->keys()->first(fn($key) => str_starts_with($key, 'wire:model'));
                    $isLive = $wireModelAttr && str_contains($wireModelAttr, '.live');
                @endphp
                const isLiveUpdate = {{ $isLive ? 'true' : 'false' }};
                const select2Options = {
                    placeholder: '{{ $placeholder }}',
                    allowClear: !isMultiple,
                    closeOnSelect: false
                };

                @if ($ajax && $ajaxUrl) select2Options.ajax = {
                url: '{{ $ajaxUrl }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            };
            select2Options.minimumInputLength = 0;
            @else
            @if ($searchable)
            select2Options.minimumResultsForSearch = 0;
            @else
            select2Options.minimumResultsForSearch = Infinity; @endif
                @endif

                this.select2Instance = $select.select2(select2Options);

                // Fix body scroll lock when dropdown closes
                $select.on('select2:open', function(e) {
                    // Don't lock body scroll
                    $('body').css('overflow', '');
                });

                $select.on('select2:close', function(e) {
                    // Ensure body scroll is restored
                    $('body').css('overflow', '');
                });

                $select.on('select2:select', function(e) {
                    // Restore scroll after selection
                    $('body').css('overflow', '');
                    if (!isMultiple) {
                        $select.select2('close');
                    }
                });

                // Handle unselect - close dropdown immediately
                $select.on('select2:unselect', function(e) {
                    // Restore scroll
                    $('body').css('overflow', '');
                    // Small delay to let Select2 process the unselect first
                    setTimeout(() => {
                        $select.select2('close');
                    }, 1);
                });

                // Allow clicking outside to close dropdown
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.select2-container').length &&
                        !$(e.target).closest('#{{ $componentId }}').length) {
                        $select.select2('close');
                        // Restore scroll
                        $('body').css('overflow', '');
                    }
                });

                // Sync Select2 changes to Livewire
                $select.on('change', (e) => {
                    @if($attributes->wire('model')->value())
                    // Only trigger live update if wire:model.live is used
                    if (isLiveUpdate) {
                        const value = $(e.target).val();
                        @if($multiple)
                        this.$wire.set('{{ $attributes->wire('model')->value() }}', value || []);
                        @else
                        this.$wire.set('{{ $attributes->wire('model')->value() }}', value || null);
                        @endif
                    }
                    // For regular wire:model, just dispatch events and let Livewire handle it
                    @endif
                });

                @if($attributes->wire('model')->value())
                // Watch Livewire model changes
                this.$watch('$wire.{{ $attributes->wire('model')->value() }}', value => {
                    const currentValue = $select.val();
                    const newValue = isMultiple ? (value || []) : value;

                    // Only update if different
                    if (JSON.stringify(currentValue) !== JSON.stringify(newValue)) {
                        $select.val(newValue).trigger('change.select2');
                    }
                });
                @endif
            } catch (error) {
                console.error('Select2 initialization error:', error);
            }
        },
        destroy() {
            if (this.select2Instance) {
                $('#{{ $componentId }}').select2('destroy');
                // Ensure body scroll is restored
                $('body').css('overflow', '');
            }
        }
    }"
    x-init="init()"
    @wire:navigate.start.window="destroy()"
>
    <select
        id="{{ $componentId }}"
        name="{{ $name }}"
        {{ $multiple ? 'multiple' : '' }}
        {{ $attributes->except(['wire:model', 'wire:model.live'])->merge(['class' => 'w-full']) }}
    >
        @if (!$multiple && !$ajax)
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
