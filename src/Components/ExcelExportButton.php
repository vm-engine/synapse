<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Models\ExcelExport;

class ExcelExportButton extends Component
{
    public string $label;

    public string $exportingLabel;

    public string $processingLabel;

    public string $downloadLabel;

    public string $failedLabel;

    public string $dismissLabel;

    public function __construct(
        public string $mode = 'async',
        public string $action = 'exportToExcel',
        public ?ExcelExport $task = null,
        public ?string $downloadAction = null,
        public ?string $dismissAction = null,
        string $label = '',
        string $exportingLabel = '',
        string $processingLabel = '',
        string $downloadLabel = '',
        string $failedLabel = '',
        string $dismissLabel = '',
        public string $pollInterval = '2s',
    ) {
        $this->label = $label ?: 'Export to Excel';
        $this->exportingLabel = $exportingLabel ?: 'Exporting...';
        $this->processingLabel = $processingLabel ?: 'Processing...';
        $this->downloadLabel = $downloadLabel ?: 'Download';
        $this->failedLabel = $failedLabel ?: 'Export failed';
        $this->dismissLabel = $dismissLabel ?: 'Remove';
    }

    public function render()
    {
        return view('synapse::components.excel-export-button');
    }
}
