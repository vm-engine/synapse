<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Transformers;

use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;

class BooleanTransformer implements ColumnTransformer
{
    public function __construct(
        public readonly string $trueLabel = 'Yes',
        public readonly string $falseLabel = 'No',
    ) {}

    public function transform(mixed $value, mixed $record): mixed
    {
        if ($value === null) {
            return null;
        }

        return $value ? $this->trueLabel : $this->falseLabel;
    }
}
