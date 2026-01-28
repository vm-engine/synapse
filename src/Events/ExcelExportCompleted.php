<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Events;

use Illuminate\Foundation\Events\Dispatchable;
use VmEngine\Synapse\Models\ExcelExport;

class ExcelExportCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly ExcelExport $task,
    ) {}
}
