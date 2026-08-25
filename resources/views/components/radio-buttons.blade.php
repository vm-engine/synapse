<div class="syn-radio-buttons">
    <!-- Elements -->
    <div
        class="syn-radio-buttons-row"
        x-data="{ selected: {{ $model ?? '' }} }"
    >
        @foreach ($options as $key => $option)
            <div>
                <label
                    class="syn-radio-label"
                    for="{{ $name }}-{{ $key }}"
                >
                    <div class="relative">
                        <input
                            class="sr-only"
                            id="{{ $name }}-{{ $key }}"
                            name="{{ $name }}"
                            type="radio"
                            value="{{ $option }}"
                            @change="selected = '{{ $key }}'"
                            {{ $attributes }}
                        />
                        <div
                            class="syn-radio-box"
                            :class="selected == '{{ $key }}' ? 'border-brand-500 bg-brand-500' :
                                'bg-transparent border-gray-300 dark:border-gray-700'"
                        >
                            <span
                                class="syn-radio-dot"
                                :class="selected == '{{ $key }}' ? 'bg-white' : 'bg-white dark:bg-[#171f2e]'"
                            ></span>
                        </div>
                    </div>
                    {{ $option }}
                </label>
            </div>
        @endforeach
    </div>
</div>
