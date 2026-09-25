@props([
    'name' => 'file',
    'wireModel' => null,
    'accept' => 'image/png,image/jpeg,image/webp',
    'maxSize' => 5120, // KB (default 5MB)
    'maxWidth' => null,
    'maxHeight' => null,
    'preview' => true,
    'label' => 'Drop File Here',
    'description' => 'Drag and drop your PNG, JPG, WebP images here or browse',
    'browseText' => 'Browse File',
    'error' => null,
])

@php
    $wireModelDirective = $wireModel ? "wire:model=\"{$wireModel}\"" : '';
    $acceptTypes = collect(explode(',', $accept))->map(fn($type) => trim($type))->join(',');
    $acceptExtensions = collect(explode(',', $accept))
        ->map(fn($type) => '.' . last(explode('/', trim($type))))
        ->join(', ');
@endphp

<div
    class="syn-drag-drop"
    x-data="synapseDragDrop({{ json_encode(['accept' => $acceptTypes, 'acceptExtensions' => $acceptExtensions, 'maxSize' => $maxSize, 'maxWidth' => $maxWidth, 'maxHeight' => $maxHeight, 'wireModel' => $wireModel ?: null]) }})"
    x-on:dragndrop-clear-input.window="removeFile()"
>
    <!-- Hidden File Input -->
    <input
        class="hidden"
        name="{{ $name }}"
        type="file"
        x-ref="fileInput"
        accept="{{ $acceptTypes }}"
        @if (!$wireModel) @change="handleFiles($event.target.files)" @endif
        {!! $wireModelDirective !!}
        {{ $attributes->except(['class']) }}
        @if ($wireModel) x-on:livewire-upload-start="handleLivewireUploadStart()"
        x-on:livewire-upload-finish="handleLivewireUploadFinish($event)"
        x-on:livewire-upload-error="handleLivewireUploadError()" @endif
    />

    <!-- Dropzone Area -->
    <div
        class="syn-drag-drop-zone"
        @click="triggerFileInput()"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="onDrop($event)"
        :class="isDragging ? 'syn-drag-drop-zone-active' : 'syn-drag-drop-zone-idle'"
    >
        <!-- Preview Area -->
        <div
            class="syn-drag-drop-preview"
            x-show="previewUrl && {{ $preview ? 'true' : 'false' }}"
            x-cloak
        >
            <div class="syn-drag-drop-preview-wrap">
                <img
                    class="syn-drag-drop-preview-img"
                    alt="Preview"
                    :src="previewUrl"
                >
                <button
                    class="syn-drag-drop-remove-btn"
                    type="button"
                    @click.stop="removeFile()"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>
            <p
                class="syn-drag-drop-filename"
                x-text="fileName"
            ></p>
            <p
                class="syn-drag-drop-filesize"
                x-text="fileSize"
            ></p>
        </div>

        <!-- Upload Icon and Text -->
        <div x-show="!previewUrl">
            <div class="syn-drag-drop-empty-icon-wrap">
                <div class="syn-drag-drop-empty-icon-circle">
                    <svg
                        class="syn-drag-drop-empty-icon"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        >
                        </path>
                    </svg>
                </div>
            </div>

            <h4 class="syn-drag-drop-label">{{ $label }}</h4>

            <p class="syn-drag-drop-desc">
                {{ $description }}
            </p>

            <span class="syn-drag-drop-browse">
                {{ $browseText }}
            </span>

            <!-- File Info (when file selected but no preview) -->
            <div
                class="syn-drag-drop-file-info"
                x-show="fileName && !previewUrl"
            >
                <p
                    class="syn-drag-drop-file-name"
                    x-text="fileName"
                ></p>
                <p
                    class="syn-drag-drop-file-size"
                    x-text="fileSize"
                ></p>
                <button
                    class="syn-drag-drop-remove-link"
                    type="button"
                    @click.stop="removeFile()"
                >
                    Remove
                </button>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <div
        class="syn-drag-drop-error"
        x-show="error"
        x-cloak
        x-text="error"
    ></div>

    @if ($error)
        <p class="syn-drag-drop-error">{{ $error }}</p>
    @endif
</div>

