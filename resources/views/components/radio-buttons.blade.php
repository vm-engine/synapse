<div class="px-5 py-2 sm:px-6 sm:py-3">
    <!-- Elements -->
    <div class="flex flex-wrap items-center gap-8" x-data="{ selected: {{ $model ?? '' }} }">
        @foreach ($options as $key => $option)
            <div>
                <label for="{{ $name }}-{{ $key }}"
                    class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400">
                    <div class="relative">
                        <input type="radio" id="{{ $name }}-{{ $key }}" value="{{ $option }}"
                            class="sr-only" wire:model="{{ $name }}" name="{{ $name }}"
                            @change="selected = '{{ $key }}'" />
                        <div :class="selected == '{{ $key }}' ? 'border-brand-500 bg-brand-500' :
                            'bg-transparent border-gray-300 dark:border-gray-700'"
                            class="hover:border-brand-500 dark:hover:border-brand-500 mr-3 flex h-5 w-5 items-center justify-center rounded-full border-[1.25px]">
                            <span class="h-2 w-2 rounded-full"
                                :class="selected == '{{ $key }}' ? 'bg-white' : 'bg-white dark:bg-[#171f2e]'"></span>
                        </div>
                    </div>
                    {{ $option }}
                </label>
            </div>
        @endforeach
    </div>
</div>
