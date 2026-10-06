{{--
    Excel Export Button Component

    Sync usage (host action returns a download response directly):
    <x-synapse-excel-export-button
        mode="sync"
        action="exportToExcel"
        label="{{ __('pm::report.documents.filters.export') }}"
        exporting-label="{{ __('pm::report.documents.filters.exporting') }}"
    />

    Async usage (host uses the WithExcelExport trait):
    <x-synapse-excel-export-button
        mode="async"
        :task="$this->exportTask"
        action="exportToExcel"
        download-action="downloadExport"
        dismiss-action="dismissExport"
        label="{{ __('pm::report.invoice.export.button') }}"
        processing-label="{{ __('pm::report.invoice.export.processing') }}"
        download-label="{{ __('pm::report.invoice.export.download') }}"
        failed-label="{{ __('pm::report.invoice.export.failed') }}"
        dismiss-label="{{ __('pm::report.invoice.export.dismiss') }}"
    />
--}}

@if ($mode === 'sync')
    <button
        class="btn success"
        type="button"
        wire:click="{{ $action }}"
        wire:loading.attr="disabled"
        wire:target="{{ $action }}"
    >
        <span
            class="fa-solid fa-file-excel mr-1"
            wire:loading.remove
            wire:target="{{ $action }}"
        ></span>
        <span
            class="fa-solid fa-spinner fa-spin mr-1"
            wire:loading
            wire:target="{{ $action }}"
        ></span>
        <span
            wire:loading.remove
            wire:target="{{ $action }}"
        >{{ $label }}</span>
        <span
            wire:loading
            wire:target="{{ $action }}"
        >{{ $exportingLabel }}</span>
    </button>
@else
    @php
        $isProcessing =
            $task &&
            in_array($task->status, [
                \VmEngine\Synapse\Enums\ExcelTaskStatus::Pending,
                \VmEngine\Synapse\Enums\ExcelTaskStatus::Processing,
            ]);
        $isCompleted =
            $task &&
            $task->status === \VmEngine\Synapse\Enums\ExcelTaskStatus::Completed &&
            $task->output_path &&
            file_exists($task->output_path);
        $isFailed = $task && $task->status === \VmEngine\Synapse\Enums\ExcelTaskStatus::Failed;
        $progressPercent =
            $isProcessing && $task->total_rows
                ? min(100, (int) round(($task->processed_rows / $task->total_rows) * 100))
                : null;
    @endphp

    <div class="flex items-center gap-3">
        @if ($isFailed)
            <span class="text-sm text-red-600 dark:text-red-400">
                <span class="fa-solid fa-exclamation-triangle mr-1"></span>
                {{ $failedLabel }}
            </span>
        @endif

        <div>
            <button
                class="btn success"
                type="button"
                wire:click="{{ $action }}"
                {{ $isProcessing ? 'disabled' : '' }}
                wire:loading.attr="disabled"
                wire:target="{{ $action }}"
            >
                <span
                    class="fa-solid fa-file-excel mr-1"
                    wire:loading.remove
                    wire:target="{{ $action }}"
                ></span>
                <span
                    class="fa-solid fa-spinner fa-spin mr-1"
                    wire:loading
                    wire:target="{{ $action }}"
                ></span>
                @if ($isProcessing)
                    <span
                        class="fa-solid fa-spinner fa-spin mr-1"
                        wire:poll.{{ $pollInterval }}="$refresh"
                    ></span>
                    {{ $processingLabel }}
                    @if ($progressPercent !== null)
                        ({{ $progressPercent }}%)
                    @endif
                @else
                    <span
                        wire:loading.remove
                        wire:target="{{ $action }}"
                    >{{ $label }}</span>
                    <span
                        wire:loading
                        wire:target="{{ $action }}"
                    >{{ $exportingLabel }}</span>
                @endif
            </button>

            @if ($isProcessing && $progressPercent !== null)
                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-full rounded-full bg-green-500 transition-all"
                        style="width: {{ $progressPercent }}%"
                    ></div>
                </div>
            @endif
        </div>

        @if ($isCompleted)
            <button
                class="btn primary"
                type="button"
                wire:click="{{ $downloadAction }}"
            >
                <span class="fa-solid fa-download mr-1"></span>
                {{ $downloadLabel }}
            </button>

            @if ($dismissAction)
                <button
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    type="button"
                    title="{{ $dismissLabel }}"
                    wire:click="{{ $dismissAction }}"
                >
                    <span class="fa-solid fa-xmark"></span>
                </button>
            @endif
        @endif
    </div>
@endif
