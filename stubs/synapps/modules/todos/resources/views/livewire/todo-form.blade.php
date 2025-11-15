<div
    class="card"
    id="todo-form"
    x-data="{
        scrollToForm() {
            this.$el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(() => {
                this.$refs.titleInput.focus();
            }, 500);
        },
        focusTitleInput() {
            setTimeout(() => {
                this.$refs.titleInput.focus();
            }, 100);
        }
    }"
    @scroll-to-form.window="scrollToForm()"
    @todo-created.window="focusTitleInput()"
>
    <div class="card-header">
        <h3 class="card-title">
            @if ($isEditing)
                {{ __('todos::todos.form.edit_title') }}
            @else
                {{ __('todos::todos.form.add_title') }}
            @endif
        </h3>
    </div>

    <div class="card-body">
        {{-- Title --}}
        <div class="form-box required">
            <label for="title">{{ __('todos::todos.form.title') }}</label>
            <div class="input-group">
                <input
                    class="form-input @error('title') has-error @enderror"
                    id="title"
                    name="title"
                    type="text"
                    x-ref="titleInput"
                    wire:model="title"
                    placeholder="{{ __('todos::todos.form.title_placeholder') }}"
                    @keydown.enter="$wire.save()"
                />
            </div>
            @error('title')
                <p class="form-error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- Detail --}}
        <div class="form-box">
            <label for="detail">{{ __('todos::todos.form.detail') }}</label>
            <div class="input-group">
                <textarea
                    class="form-input @error('detail') has-error @enderror"
                    id="detail"
                    name="detail"
                    rows="3"
                    wire:model="detail"
                    placeholder="{{ __('todos::todos.form.detail_placeholder') }}"
                ></textarea>
            </div>
            @error('detail')
                <p class="form-error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tags --}}
        <div class="form-box">
            <label for="tag">{{ __('todos::todos.form.tags') }}</label>
            @php
                // Convert tag array to format expected by adv-select
                $tagPreload = collect($tag)->map(fn($t) => ['value' => $t, 'label' => $t])->toArray();
            @endphp
            <x-synapse-adv-select
                wire-model="tag"
                :multiple="true"
                :allow-create="true"
                :placeholder="__('todos::todos.form.tags_placeholder')"
                :preload="$tagPreload"
                :options="[]"
            />
            @error('tag')
                <p class="form-error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- Due Date --}}
        <div class="form-box">
            <label for="due_date">{{ __('todos::todos.form.due_date') }}</label>
            @php
                $flatpickr = json_encode([
                    'enableTime' => true,
                    'time_24hr' => true,
                    'dateFormat' => 'Y-m-d H:i',
                    'minDate' => 'today',
                ]);
            @endphp
            <x-synapse-datepicker
                id="due_date"
                name="due_date"
                :flatpickr="$flatpickr"
                wire:model="due_date"
            />
            @error('due_date')
                <p class="form-error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-gray-800">
            <button
                class="btn secondary"
                type="button"
                wire:click="resetForm"
            >
                <span class="fa-solid fa-rotate-right"></span>
                {{ __('todos::todos.buttons.reset') }}
            </button>

            @if ($isEditing)
                <button
                    class="btn secondary"
                    type="button"
                    wire:click="cancelEdit"
                >
                    <span class="fa-solid fa-times"></span>
                    {{ __('todos::todos.buttons.cancel_edit') }}
                </button>
            @endif

            <button
                class="btn primary"
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span class="fa-solid fa-floppy-disk"></span>
                <span
                    wire:loading.remove
                    wire:target="save"
                >
                    @if ($isEditing)
                        {{ __('todos::todos.buttons.update') }}
                    @else
                        {{ __('todos::todos.buttons.save') }}
                    @endif
                </span>
                <span
                    wire:loading
                    wire:target="save"
                >
                    {{ __('todos::todos.buttons.saving') }}
                </span>
            </button>
        </div>
    </div>
</div>
