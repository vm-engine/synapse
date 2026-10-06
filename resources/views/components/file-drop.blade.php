<div
    class="synapse-file-drop"
    x-data="synapseFileDrop()"
>
    @if ($isFull())
        <p class="synapse-file-drop__full">{{ $resolvedFullText() }}</p>
    @else
        <label
            class="synapse-file-drop__zone"
            :class="{ 'is-dragging': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)"
        >
            <span
                class="synapse-file-drop__icon"
                aria-hidden="true"
            >
                <i class="ph ph-cloud-arrow-up"></i>
            </span>
            <span class="synapse-file-drop__primary">{!! $resolvedLabel() !!}</span>
            @if ($resolvedHint())
                <span class="synapse-file-drop__hint">{{ $resolvedHint() }}</span>
            @endif
            @if ($counterText())
                <span class="synapse-file-drop__counter">{{ $counterText() }}</span>
            @endif
            <input
                class="synapse-file-drop__input"
                type="file"
                name="{{ $name }}"
                x-ref="input"
                @if ($wireModel) wire:model="{{ $wireModel }}" @endif
                @if ($acceptString()) accept="{{ $acceptString() }}" @endif
                @if ($multiple) multiple @endif
                {{ $attributes->except(['class']) }}
            >
        </label>
    @endif

    @if ($error)
        <p class="synapse-file-drop__error">{{ $error }}</p>
    @endif

    @if ($wireModel)
        <div
            class="synapse-file-drop__uploading"
            wire:loading
            wire:target="{{ $wireModel }}"
        >
            {{ $resolvedUploadingText() }}
        </div>
    @endif
</div>

