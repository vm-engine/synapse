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
    x-data="{
        fileName: '',
        fileSize: '',
        previewUrl: null,
        isDragging: false,
        error: @js($error),
        accept: @js($acceptTypes),
        maxSize: @js($maxSize),
        maxWidth: @js($maxWidth),
        maxHeight: @js($maxHeight),

        handleFiles(files) {
            if (files.length === 0) return;

            const file = files[0];
            this.error = null;

            // Validate file type
            if (this.accept && !this.accept.split(',').some(type => file.type === type.trim())) {
                this.error = 'Invalid file type. Accepted types: ' + @js($acceptExtensions);
                this.clearFileInput();
                return;
            }

            // Validate file size
            const fileSizeKB = Math.round(file.size / 1024);
            if (this.maxSize && fileSizeKB > this.maxSize) {
                this.error = `File size exceeds maximum allowed size of ${this.maxSize}KB (${Math.round(this.maxSize / 1024)}MB)`;
                this.clearFileInput();
                return;
            }

            // Set preview immediately - dimension validation will be done server-side
            this.setFileInfo(file);
        },

        setFileInfo(file) {
            this.fileName = file.name;
            this.fileSize = this.formatFileSize(file.size);

            // Show preview for images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        clearFileInput() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }

            @if($wireModel)
            this.$wire.set('{{ $wireModel }}', null);
            @endif
        },

        removeFile() {
            this.fileName = '';
            this.fileSize = '';
            this.previewUrl = null;
            this.error = null;
            this.clearFileInput();
        },

        triggerFileInput() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.click();
            }
        },

        // Handle drag-and-drop for Livewire mode
        handleDrop(event) {
            const files = event.dataTransfer.files;

            if (files.length > 0 && this.$refs.fileInput) {
                // Set the files to the input element so Livewire can pick them up
                this.$refs.fileInput.files = files;

                // Manually trigger change event for Livewire to detect
                const changeEvent = new Event('change', { bubbles: true });
                this.$refs.fileInput.dispatchEvent(changeEvent);
            }
        },

        // Livewire upload event handlers
        handleLivewireUploadStart() {
            this.error = null;

            // Get file info from the input
            if (this.$refs.fileInput && this.$refs.fileInput.files.length > 0) {
                const file = this.$refs.fileInput.files[0];
                this.fileName = file.name;
                this.fileSize = this.formatFileSize(file.size);

                // Show preview for images
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.previewUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            }
        },

        handleLivewireUploadFinish(event) {
            // Upload completed successfully
        },

        handleLivewireUploadError() {
            this.error = 'Upload failed. Please try again.';
            this.removeFile();
        }
    }"
    x-init="
        // Watch for wire:model changes to clear preview
        @if($wireModel)
        $watch('$wire.{{ $wireModel }}', value => {
            if (value === null || value === '') {
                // Only clear the UI, don't touch the file input during upload
                this.fileName = '';
                this.fileSize = '';
                this.previewUrl = null;
            }
        });
        @endif
    "
    class="w-full"
>
    <!-- Hidden File Input -->
    <input
        type="file"
        x-ref="fileInput"
        name="{{ $name }}"
        accept="{{ $acceptTypes }}"
        @if(!$wireModel)
        @change="handleFiles($event.target.files)"
        @endif
        {!! $wireModelDirective !!}
        class="hidden"
        {{ $attributes->except(['class']) }}
        @if($wireModel)
        x-on:livewire-upload-start="handleLivewireUploadStart()"
        x-on:livewire-upload-finish="handleLivewireUploadFinish($event)"
        x-on:livewire-upload-error="handleLivewireUploadError()"
        @endif
    />

    <!-- Dropzone Area -->
    <div
        @click="triggerFileInput()"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="isDragging = false; @if($wireModel) handleDrop($event) @else handleFiles($event.dataTransfer.files) @endif"
        :class="isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-white'"
        class="relative cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors duration-200 hover:border-gray-400"
    >
        <!-- Preview Area -->
        <div x-show="previewUrl && {{ $preview ? 'true' : 'false' }}" x-cloak class="mb-4">
            <div class="relative mx-auto inline-block">
                <img :src="previewUrl" alt="Preview" class="max-h-48 rounded-lg shadow-md">
                <button
                    type="button"
                    @click.stop="removeFile()"
                    class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-lg hover:bg-red-600"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p class="mt-2 text-sm text-gray-600" x-text="fileName"></p>
            <p class="text-xs text-gray-500" x-text="fileSize"></p>
        </div>

        <!-- Upload Icon and Text -->
        <div x-show="!previewUrl">
            <div class="mb-6 flex justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                </div>
            </div>

            <h4 class="mb-3 text-xl font-semibold text-gray-700">{{ $label }}</h4>

            <p class="mx-auto mb-5 block max-w-sm text-sm text-gray-500">
                {{ $description }}
            </p>

            <span class="text-sm font-medium text-blue-600 underline hover:text-blue-700">
                {{ $browseText }}
            </span>

            <!-- File Info (when file selected but no preview) -->
            <div x-show="fileName && !previewUrl" class="mt-4">
                <p class="text-sm font-medium text-gray-700" x-text="fileName"></p>
                <p class="text-xs text-gray-500" x-text="fileSize"></p>
                <button
                    type="button"
                    @click.stop="removeFile()"
                    class="mt-2 text-xs text-red-600 hover:text-red-700"
                >
                    Remove
                </button>
            </div>
        </div>
    </div>

    <!-- Error Message -->
    <div x-show="error" x-cloak class="mt-2 text-sm text-red-600" x-text="error"></div>

    @if($error)
    <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
    @endif
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
