<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Throwable;
use VmEngine\Synapse\Models\ExcelExport;

class ExcelExportFailed
{
    use Dispatchable;

    public function __construct(
        public readonly ExcelExport $task,
        public readonly Throwable $exception,
    ) {}
}
