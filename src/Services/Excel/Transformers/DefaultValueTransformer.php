<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Transformers;

use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;

class DefaultValueTransformer implements ColumnTransformer
{
    public function __construct(
        public readonly string $default = '-',
    ) {}

    public function transform(mixed $value, mixed $record): mixed
    {
        if ($value === null || $value === '') {
            return $this->default;
        }

        return $value;
    }
}
