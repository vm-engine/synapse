@props([
    'type' => 'default',
    'title' => '',
    'description' => '',
    'icon' => 'fa-file',
    'documents' => [],
    'multipleFiles' => false,
])

<div
    class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
    x-data="{ expanded: true }"
>
    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-4 sm:px-6">
        <div class="flex cursor-pointer items-center gap-3 flex-1" @click="expanded = !expanded">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10">
                <span class="{{ $icon }} text-blue-600 dark:text-blue-400"></span>
            </div>
            <div>
                <h4 class="font-medium text-gray-800 dark:text-white/90">
                    {{ $title }}
                    <span class="ml-2 text-sm font-normal text-gray-500">
                        ({{ count($documents) }})
                    </span>
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            {{-- Upload Button --}}
            <button
                class="btn primary btn-sm"
                wire:click="uploadDocument('{{ $type }}')"
                @click.stop
            >
                <span class="fa-solid fa-upload mr-1"></span>
                <span class="hidden sm:inline">Upload</span>
            </button>

            {{-- Badge and Chevron --}}
            <div class="flex cursor-pointer items-center gap-3" @click="expanded = !expanded">
                @if($multipleFiles)
                    <span class="badge badge-info badge-sm">
                        <span class="fa-solid fa-layer-group mr-1"></span>
                        <span class="hidden md:inline">Multiple Files</span>
                    </span>
                @else
                    <span class="badge badge-primary badge-sm">
                        <span class="fa-solid fa-clock-rotate-left mr-1"></span>
                        <span class="hidden md:inline">Versioned</span>
                    </span>
                @endif
                <span
                    class="fa-solid text-gray-400 transition-transform"
                    :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"
                ></span>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div
        x-show="expanded"
        x-collapse
        class="border-t border-gray-100 dark:border-gray-800"
    >
        @if(count($documents) === 0)
            {{-- Empty State --}}
            <div class="px-5 py-8 text-center sm:px-6">
                <span class="{{ $icon }} fa-3x mb-3 text-gray-300 dark:text-gray-700"></span>
                <p class="text-sm text-gray-500 dark:text-gray-500">
                    No documents uploaded yet
                </p>
                <button class="btn primary btn-sm mt-3" wire:click="uploadDocument('{{ $type }}')">
                    <span class="fa-solid fa-upload mr-2"></span>
                    Upload First Document
                </button>
            </div>
        @else
            {{-- Documents List --}}
            @if($multipleFiles)
                {{-- Multiple Files Layout --}}
                <x-synapse-document-multiple-files-list :documents="$documents" :type="$type" />
            @else
                {{-- Single File with Revisions Layout --}}
                <x-synapse-document-revision-list :documents="$documents" :type="$type" />
            @endif
        @endif
    </div>
</div>
