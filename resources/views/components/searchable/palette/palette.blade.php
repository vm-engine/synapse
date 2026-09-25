<div>
    @if ($enabled)
        {{-- Alpine logic lives here, not in attributes: the app runs Alpine's CSP build,
             whose expression parser rejects arrow functions, ?. and ??. --}}

        <button
            type="button"
            class="synsearch-trigger"
            x-data
            x-on:click="$dispatch('open-modal-synsearch')"
            x-on:keydown.window.ctrl.k.prevent="$dispatch('open-modal-synsearch')"
            x-on:keydown.window.meta.k.prevent="$dispatch('open-modal-synsearch')"
            aria-label="{{ __('Search') }}"
        >
            <i
                class="ph ph-magnifying-glass"
                aria-hidden="true"
            ></i>
            <kbd class="synsearch-kbd">Ctrl K</kbd>
        </button>

        {{-- Teleported: the header group holding the trigger is display:none on
             mobile until toggled, which would hide a modal nested inside it. --}}
        @teleport('body')
            <div>
                <x-synapse::modal
                    name="synsearch"
                    max-width="2xl"
                >
                    <div
                        class="synsearch-panel"
                        x-data="synsearchPalette"
                        x-on:keydown.arrow-down.prevent="next()"
                        x-on:keydown.arrow-up.prevent="prev()"
                        x-on:keydown.enter.prevent="openActive()"
                    >
                        <div class="synsearch-input-wrap">
                            <i
                                class="ph ph-magnifying-glass synsearch-input-icon"
                                aria-hidden="true"
                            ></i>
                            <input
                                type="text"
                                class="synsearch-input"
                                x-ref="input"
                                wire:model.live.debounce.250ms="term"
                                x-on:input="reset()"
                                placeholder="{{ __('Search pages and content…') }}"
                                autocomplete="off"
                                aria-label="{{ __('Search') }}"
                            />
                            <span
                                class="synsearch-loading"
                                wire:loading.delay
                                wire:target="term"
                            >{{ __('Searching…') }}</span>
                        </div>

                        @if (mb_strlen(trim($term)) < 2)
                            <p class="synsearch-empty">{{ __('Type at least 2 characters to search.') }}</p>
                        @elseif ($this->results->isEmpty())
                            <p class="synsearch-empty">{{ __('No results found.') }}</p>
                        @else
                            <ul
                                class="synsearch-list"
                                x-ref="list"
                            >
                                @foreach ($this->results as $index => $result)
                                    <li wire:key="synsearch-{{ $result->id }}">
                                        <a
                                            href="{{ $result->resolveUrl() }}"
                                            wire:navigate
                                            class="synsearch-item"
                                            x-bind:class="{ 'synsearch-item-active': active === {{ $index }} }"
                                            x-on:mouseenter="active = {{ $index }}"
                                            x-on:click="$dispatch('close-modal-synsearch')"
                                        >
                                            <i
                                                class="{{ $result->icon ?: 'ph ph-file' }} synsearch-item-icon"
                                                aria-hidden="true"
                                            ></i>
                                            <span class="synsearch-item-body">
                                                <span class="synsearch-item-title">{{ $result->title }}</span>
                                                @if ($result->description)
                                                    <span class="synsearch-item-description">{{ $result->description }}</span>
                                                @endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </x-synapse::modal>
            </div>
        @endteleport
    @endif
</div>
