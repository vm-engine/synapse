<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Contracts;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

interface ExportQuery
{
    /**
     * Build and return the query for export.
     *
     * @return EloquentBuilder<Model>|QueryBuilder
     */
    public function __invoke(): EloquentBuilder|QueryBuilder;
}
