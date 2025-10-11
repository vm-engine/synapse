<div class="px-5 py-2 sm:px-6 sm:py-3">
    <!-- Elements -->
    <div class="flex flex-wrap items-center gap-8">
        @foreach ($options as $key => $option)
            <div x-data="{ checked: {{ in_array($key, $model) ? 'true' : 'false' }} }">
                <label
                    class="flex cursor-pointer items-center text-sm font-medium text-gray-700 select-none dark:text-gray-400"
                    for="{{ $name }}-{{ $key }}"
                >
                    <div class="relative">
                        <input
                            class="sr-only"
                            id="{{ $name }}-{{ $key }}"
                            name="{{ $name }}[]"
                            type="checkbox"
                            value="{{ $option }}"
                            @change="checked = !checked"
                            {{ $attributes }}
                        />
                        <div
                            class="f hover:border-brand-500 dark:hover:border-brand-500 mr-3 flex h-5 w-5 items-center justify-center rounded-md border-[1.25px]"
                            :class="checked ? 'border-brand-500 bg-brand-500' :
                                'bg-transparent border-gray-300 dark:border-gray-700'"
                        >
                            <span :class="checked ? '' : 'opacity-0'">
                                <svg
                                    width="14"
                                    height="14"
                                    viewBox="0 0 14 14"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M11.6666 3.5L5.24992 9.91667L2.33325 7"
                                        stroke="white"
                                        stroke-width="1.94437"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                        </div>
                    </div>
                    {{ $option }}
                </label>
            </div>
        @endforeach
    </div>
</div>
