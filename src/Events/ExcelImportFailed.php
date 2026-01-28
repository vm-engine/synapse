<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Throwable;
use VmEngine\Synapse\Models\ExcelImport;

class ExcelImportFailed
{
    use Dispatchable;

    public function __construct(
        public readonly ExcelImport $task,
        public readonly Throwable $exception,
    ) {}
}
