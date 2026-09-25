<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Support;

use Closure;

class ColumnMapper
{
    /**
     * @var array<int|string, int|string>|Closure|null
     */
    private array|Closure|null $mapping = null;

    /**
     * Set the column mapping.
     *
     * Accepts:
     * - Sequential array: ['name', 'email', 'phone'] (positional mapping)
     * - Associative array: ['name' => 0, 'email' => 2] (explicit column index)
     * - Closure: fn(array $row, int $rowIndex) => ['name' => $row[0]]
     *
     * @param  array<int|string, int|string>|Closure  $mapping
     */
    public function setMapping(array|Closure $mapping): void
    {
        $this->mapping = $mapping;
    }

    /**
     * Get the current mapping.
     *
     * @return array<int|string, int|string>|Closure|null
     */
    public function getMapping(): array|Closure|null
    {
        return $this->mapping;
    }

    /**
     * Map a raw row using the configured mapping.
     *
     * @param  array<int, mixed>  $row  Raw row data (0-indexed)
     * @param  int  $rowIndex  The current row index in the file
     * @return array<array-key, mixed> 0-indexed raw row when no mapping is set
     */
    public function map(array $row, int $rowIndex): array
    {
        if ($this->mapping === null) {
            return $row;
        }

        if ($this->mapping instanceof Closure) {
            return ($this->mapping)($row, $rowIndex);
        }

        return $this->mapWithArray($row);
    }

    /**
     * Map using an array mapping definition.
     *
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapWithArray(array $row): array
    {
        /** @var array<int|string, int|string> $mapping */
        $mapping = $this->mapping;
        $mapped = [];

        if ($this->isSequentialMapping($mapping)) {
            // Sequential: ['name', 'email', 'phone'] => map by position
            foreach ($mapping as $index => $key) {
                $mapped[(string) $key] = $row[$index] ?? null;
            }
        } else {
            // Associative: ['name' => 0, 'email' => 2] => map by explicit index
            foreach ($mapping as $key => $columnIndex) {
                $mapped[(string) $key] = $row[(int) $columnIndex] ?? null;
            }
        }

        return $mapped;
    }

    /**
     * Check if the mapping is sequential (list of column names).
     *
     * @param  array<int|string, int|string>  $mapping
     */
    private function isSequentialMapping(array $mapping): bool
    {
        return array_is_list($mapping);
    }

    /**
     * Serialize the mapping for storage (used in background jobs).
     *
     * @return array<int|string, int|string>|null
     */
    public function toSerializable(): ?array
    {
        if ($this->mapping === null || $this->mapping instanceof Closure) {
            return null;
        }

        return $this->mapping;
    }

    /**
     * Create a ColumnMapper from serialized data.
     *
     * @param  array<int|string, int|string>|null  $data
     */
    public static function fromSerializable(?array $data): self
    {
        $mapper = new self;

        if ($data !== null) {
            $mapper->setMapping($data);
        }

        return $mapper;
    }
}
