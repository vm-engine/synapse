<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Contracts;

use Illuminate\Support\Collection;

interface ProcessesImport
{
    /**
     * Process a chunk of imported rows.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  int  $chunkIndex  0-based chunk counter
     */
    public function process(Collection $rows, int $chunkIndex): void;
}
