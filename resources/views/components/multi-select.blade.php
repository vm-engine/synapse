{{--
    Multi-Select Component

    Usage with Livewire:
    The component uses a native <select multiple> element which automatically returns an array.
    Simply use wire:model on the component and it will work with array properties.
--}}
<div>
    <select
        class="hidden"
        id="{{ $name }}"
        name="{{ $name }}"
        x-cloak
        multiple
        {{ $attributes }}
    >
        @foreach ($options as $key => $option)
            <option
                value="{{ $key }}"
                {{ in_array($key, $model) ? 'selected' : '' }}
            >{{ $option }}</option>
        @endforeach
    </select>

    <div
        class="flex flex-col items-center"
        x-data="dropdown()"
        x-init="loadOptions('{{ $name }}')"
    >
        <div class="relative z-20 inline-block w-full">
            <div class="relative flex flex-col items-center">
                <div
                    class="w-full"
                    id="multi-select-{{ $name }}"
                    @click="open"
                >
                    <div
                        {{ $attributes->merge(['class' => 'form-input shadow-theme-xs focus:border-brand-300 focus:shadow-focus-ring dark:focus:border-brand-300 mb-2 flex h-11 rounded-lg border border-gray-300 py-1.5 pr-3 pl-3 outline-hidden transition dark:border-gray-700 dark:bg-gray-900'])->only('class') }}>
                        <div class="flex flex-auto flex-wrap gap-2">
                            <template
                                x-for="(option,index) in selected"
                                :key="index"
                            >
                                <div
                                    class="group flex items-center justify-center rounded-full border-[0.7px] border-transparent bg-gray-100 py-1 pr-2 pl-2.5 text-sm text-gray-800 hover:border-gray-200 dark:bg-gray-800 dark:text-white/90 dark:hover:border-gray-800">
                                    <div
                                        class="max-w-full flex-initial"
                                        x-model="options[option]"
                                        x-text="options[option].text"
                                    ></div>
                                    <div class="flex flex-auto flex-row-reverse">
                                        <div
                                            class="cursor-pointer pl-2 text-gray-500 group-hover:text-gray-400 dark:text-gray-400"
                                            @click="remove(index,option)"
                                        >
                                            <svg
                                                class="fill-current"
                                                role="button"
                                                width="14"
                                                height="14"
                                                viewBox="0 0 14 14"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    clip-rule="evenodd"
                                                    d="M3.40717 4.46881C3.11428 4.17591 3.11428 3.70104 3.40717 3.40815C3.70006 3.11525 4.17494 3.11525 4.46783 3.40815L6.99943 5.93975L9.53095 3.40822C9.82385 3.11533 10.2987 3.11533 10.5916 3.40822C10.8845 3.70112 10.8845 4.17599 10.5916 4.46888L8.06009 7.00041L10.5916 9.53193C10.8845 9.82482 10.8845 10.2997 10.5916 10.5926C10.2987 10.8855 9.82385 10.8855 9.53095 10.5926L6.99943 8.06107L4.46783 10.5927C4.17494 10.8856 3.70006 10.8856 3.40717 10.5927C3.11428 10.2998 3.11428 9.8249 3.40717 9.53201L5.93877 7.00041L3.40717 4.46881Z"
                                                    fill=""
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div
                                class="flex-1"
                                x-show="selected.length == 0"
                            >
                                <input
                                    class="h-full w-full appearance-none border-0 bg-transparent p-1 pr-2 text-sm outline-hidden placeholder:text-gray-800 focus:border-0 focus:ring-0 focus:outline-hidden dark:placeholder:text-white/90"
                                    placeholder="Select option"
                                    readonly
                                />
                            </div>
                        </div>
                        <div class="flex w-7 items-center py-1 pr-1 pl-1">
                            <button
                                class="h-5 w-5 cursor-pointer text-gray-700 outline-hidden focus:outline-hidden dark:text-gray-400"
                                type="button"
                                @click="open"
                                :class="isOpen() === true ? 'rotate-180' : ''"
                            >
                                <svg
                                    class="stroke-current"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M4.79175 7.39551L10.0001 12.6038L15.2084 7.39551"
                                        stroke=""
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="w-full px-4">
                    <div
                        class="max-h-select absolute top-full left-0 z-40 w-full overflow-y-auto rounded-lg bg-white shadow-sm dark:bg-gray-900"
                        x-show.transition.origin.top="isOpen()"
                        @click.outside="close"
                    >
                        <div class="flex w-full flex-col">
                            <template
                                x-for="(option,index) in options"
                                :key="index"
                            >
                                <div class="multi-select-item">
                                    <div
                                        class="hover:bg-primary/5 w-full cursor-pointer rounded-t border-b border-gray-200 dark:border-gray-800"
                                        @click="select(index,$event)"
                                    >
                                        <div
                                            class="relative flex w-full items-center border-l-2 border-transparent p-2 pl-2"
                                            :class="option.selected ? 'border-primary' : ''"
                                        >
                                            <div class="flex w-full items-center">
                                                <div
                                                    class="mx-2 leading-6 text-gray-800 dark:text-white/90"
                                                    x-model="option"
                                                    x-text="option.text"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function dropdown() {
        return {
            options: [],
            selected: [],
            show: false,
            open() {
                this.show = true;
            },
            close() {
                this.show = false;
            },
            isOpen() {
                return this.show === true;
            },
            select(index, event) {
                if (!this.options[index].selected) {
                    this.options[index].selected = true;
                    this.options[index].element = event.target;
                    this.selected.push(index);
                } else {
                    this.selected.splice(this.selected.lastIndexOf(index), 1);
                    this.options[index].selected = false;
                }
                this.syncSelectElement();
            },
            remove(index, option) {
                this.options[option].selected = false;
                this.selected.splice(index, 1);
                this.syncSelectElement();
            },
            syncSelectElement() {
                // Update the native select element's selected options
                const selectEl = document.getElementById('{{ $name }}');
                if (selectEl) {
                    // Get all option elements
                    const selectOptions = selectEl.options;

                    // Update selected state for each option
                    for (let i = 0; i < selectOptions.length; i++) {
                        const optionValue = selectOptions[i].value;
                        const isSelected = this.selectedValuesArray().includes(optionValue);
                        selectOptions[i].selected = isSelected;
                    }

                    // Dispatch change event for Livewire to pick up
                    selectEl.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    selectEl.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            },
            loadOptions(el) {
                const options = document.getElementById(el).options;
                for (let i = 0; i < options.length; i++) {
                    var selected = options[i].getAttribute("selected") != null ?
                        true : false;
                    this.options.push({
                        value: options[i].value,
                        text: options[i].innerText,
                        selected: selected,
                    });
                    if (selected) {
                        this.selected.push(i);
                    }
                }
            },
            selectedValuesArray() {
                return this.selected.map((option) => {
                    return this.options[option].value;
                });
            },
        };
    }
</script>
