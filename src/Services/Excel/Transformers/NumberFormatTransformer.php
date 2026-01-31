<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Transformers;

use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;

class NumberFormatTransformer implements ColumnTransformer
{
    public function __construct(
        public readonly int $decimals = 2,
        public readonly string $decimalSeparator = '.',
        public readonly string $thousandsSeparator = ',',
    ) {}

    public function transform(mixed $value, mixed $record): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return number_format(
                (float) $value,
                $this->decimals,
                $this->decimalSeparator,
                $this->thousandsSeparator,
            );
        }

        return $value;
    }
}
