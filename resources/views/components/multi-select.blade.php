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
        class="syn-multi-select"
        x-data="synapseMultiSelect('{{ $name }}')"
    >
        <div class="relative z-20 inline-block w-full">
            <div class="relative flex flex-col items-center">
                <div
                    class="w-full"
                    id="multi-select-{{ $name }}"
                    @click="open()"
                >
                    <div {{ $attributes->merge(['class' => 'form-input syn-multi-select-box'])->only('class') }}>
                        <div class="syn-multi-select-tags">
                            <template
                                x-for="(option,index) in selected"
                                :key="index"
                            >
                                <div class="syn-multi-select-tag group">
                                    <div
                                        class="max-w-full flex-initial"
                                        x-model="options[option]"
                                        x-text="options[option].text"
                                    ></div>
                                    <div class="flex flex-auto flex-row-reverse">
                                        <div
                                            class="syn-multi-select-tag-remove"
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
                                class="syn-multi-select-placeholder-wrap"
                                x-show="selected.length == 0"
                            >
                                <input
                                    class="syn-multi-select-placeholder-input"
                                    placeholder="Select option"
                                    readonly
                                />
                            </div>
                        </div>
                        <div class="flex w-7 items-center py-1 pr-1 pl-1">
                            <button
                                class="syn-multi-select-toggle"
                                type="button"
                                @click="open()"
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
                        class="syn-multi-select-dropdown"
                        x-show.transition.origin.top="isOpen()"
                        @click.outside="close()"
                    >
                        <div class="flex w-full flex-col">
                            <template
                                x-for="(option,index) in options"
                                :key="index"
                            >
                                <div class="multi-select-item">
                                    <div
                                        class="syn-multi-select-option-row"
                                        @click="select(index,$event)"
                                    >
                                        <div
                                            class="syn-multi-select-option"
                                            :class="option.selected ? 'border-primary' : ''"
                                        >
                                            <div class="flex w-full items-center">
                                                <div
                                                    class="syn-multi-select-option-label"
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

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
            Alpine.data('synapseMultiSelect', (name) => ({
                name,
                options: [],
                selected: [],
                show: false,
                init() {
                    this.loadOptions();
                },
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
                    const selectEl = document.getElementById(this.name);
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
                loadOptions() {
                    const options = document.getElementById(this.name).options;
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
            }));
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
