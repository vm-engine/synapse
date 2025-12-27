@props(['documents', 'type'])

{{-- Grid Layout for Multiple Files --}}
<div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3 sm:p-6">
    @foreach($documents as $document)
        <div class="group relative rounded-lg border border-gray-200 bg-white p-4 transition-shadow hover:shadow-md dark:border-gray-700 dark:bg-gray-800/50">
            {{-- File Icon & Type --}}
            <div class="mb-3 flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-800">
                    @if(isset($document->file_extension))
                        @switch($document->file_extension)
                            @case('pdf')
                                <span class="fa-regular fa-file-pdf text-2xl text-red-600 dark:text-red-400"></span>
                                @break
                            @case('docx')
                            @case('doc')
                                <span class="fa-regular fa-file-word text-2xl text-blue-600 dark:text-blue-400"></span>
                                @break
                            @case('xlsx')
                            @case('xls')
                                <span class="fa-regular fa-file-excel text-2xl text-green-600 dark:text-green-400"></span>
                                @break
                            @case('pptx')
                            @case('ppt')
                                <span class="fa-regular fa-file-powerpoint text-2xl text-orange-600 dark:text-orange-400"></span>
                                @break
                            @default
                                <span class="fa-regular fa-file text-2xl text-gray-600 dark:text-gray-400"></span>
                        @endswitch
                    @else
                        <span class="fa-regular fa-file text-2xl text-gray-600 dark:text-gray-400"></span>
                    @endif
                </div>

                {{-- Quick Actions (Show on Hover) --}}
                <div class="flex gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                    <button
                        class="flex h-7 w-7 items-center justify-center rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300"
                        wire:click="downloadDocument({{ $document->id ?? 0 }})"
                        title="Download"
                    >
                        <span class="fa-solid fa-download text-xs"></span>
                    </button>
                    @can('pm.project.update')
                        <button
                            class="flex h-7 w-7 items-center justify-center rounded-md bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400"
                            wire:click="confirmDeleteDocument({{ $document->id ?? 0 }})"
                            title="Delete"
                        >
                            <span class="fa-solid fa-trash text-xs"></span>
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Document Info --}}
            <div class="min-w-0">
                <h6 class="truncate font-medium text-gray-800 dark:text-white/90" title="{{ $document->title ?? 'Untitled' }}">
                    {{ $document->title ?? 'Untitled Document' }}
                </h6>
                <p class="mt-1 truncate text-xs text-gray-500" title="{{ $document->original_filename ?? '' }}">
                    {{ $document->original_filename ?? 'document.pdf' }}
                </p>
            </div>

            {{-- Metadata --}}
            <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                <div class="flex items-center">
                    <span class="fa-solid fa-calendar w-4 mr-2"></span>
                    <span>{{ isset($document->created_at) ? $document->created_at->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div class="flex items-center">
                    <span class="fa-solid fa-user w-4 mr-2"></span>
                    <span class="truncate">{{ $document->uploader->name ?? 'Unknown' }}</span>
                </div>
                <div class="flex items-center">
                    <span class="fa-solid fa-hard-drive w-4 mr-2"></span>
                    <span>{{ $document->file_size_formatted ?? 'N/A' }}</span>
                </div>
            </div>

            {{-- Description (if exists) --}}
            @if(isset($document->description) && $document->description)
                <p class="mt-3 line-clamp-2 text-xs text-gray-600 dark:text-gray-400" title="{{ $document->description }}">
                    {{ $document->description }}
                </p>
            @endif

            {{-- Primary Action Button --}}
            <button
                class="btn primary btn-sm mt-3 w-full"
                wire:click="viewDocument({{ $document->id ?? 0 }})"
            >
                <span class="fa-solid fa-eye mr-1"></span>
                View Details
            </button>
        </div>
    @endforeach

    {{-- Add New File Card --}}
    @can('pm.project.create')
        <button
            class="flex min-h-[200px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 transition-colors hover:border-blue-400 hover:bg-blue-50/50 dark:border-gray-700 dark:bg-gray-800/30 dark:hover:border-blue-500 dark:hover:bg-blue-500/5"
            wire:click="uploadDocument('{{ $type }}')"
        >
            <span class="fa-solid fa-plus text-3xl text-gray-400 dark:text-gray-600"></span>
            <span class="mt-2 text-sm font-medium text-gray-600 dark:text-gray-400">
                Add New File
            </span>
        </button>
    @endcan
</div>
