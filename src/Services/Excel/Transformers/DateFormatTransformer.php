<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Transformers;

use VmEngine\Synapse\Services\Excel\Contracts\ColumnTransformer;

class DateFormatTransformer implements ColumnTransformer
{
    public function __construct(
        public readonly string $format = 'Y-m-d',
    ) {}

    public function transform(mixed $value, mixed $record): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->format);
        }

        if (is_string($value)) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value)
                ?: \DateTimeImmutable::createFromFormat('Y-m-d', $value);

            return $date !== false ? $date->format($this->format) : $value;
        }

        return $value;
    }
}
