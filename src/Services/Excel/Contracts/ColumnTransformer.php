<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Contracts;

interface ColumnTransformer
{
    /**
     * Transform a column value.
     */
    public function transform(mixed $value, mixed $record): mixed;
}
