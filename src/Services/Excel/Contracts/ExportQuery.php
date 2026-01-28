<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

interface ExportQuery
{
    /**
     * Build and return the query for export.
     */
    public function __invoke(): EloquentBuilder|QueryBuilder;
}
