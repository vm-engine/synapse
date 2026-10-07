<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Contracts;

use VmEngine\Synapse\Models\ExcelImport;

/**
 * Optional companion to ProcessesImport: the background import job hands the
 * processor its ExcelImport task before the first chunk, so it can compute
 * row numbers (options.starting_row / chunk_size), run as the importing user
 * (user_id) or store its own report in options.
 */
interface ReceivesImportTask
{
    public function setImportTask(ExcelImport $task): void;
}
