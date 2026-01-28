<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Contracts;

use Generator;

interface ReaderInterface
{
    /**
     * Open the file for reading.
     */
    public function open(string $filePath): void;

    /**
     * Read all rows from the file as a generator.
     *
     * @return Generator<int, array<int, mixed>>
     */
    public function rows(int $sheetIndex = 0): Generator;

    /**
     * Close the reader and free resources.
     */
    public function close(): void;
}
