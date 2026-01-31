<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Transformers;

use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;

class UpperCaseTransformer implements ColumnTransformer
{
    public function transform(mixed $value, mixed $record): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return strtoupper($value);
        }

        return $value;
    }
}
