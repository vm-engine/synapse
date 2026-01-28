<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Contracts;

interface WriterInterface
{
    /**
     * Open the writer for the given output path.
     */
    public function open(string $outputPath, ?string $sheetName = null): void;

    /**
     * Write a single row of data.
     *
     * @param  array<int|string, mixed>  $row
     */
    public function writeRow(array $row): void;

    /**
     * Close the writer and finalize the file.
     */
    public function close(): void;
}
