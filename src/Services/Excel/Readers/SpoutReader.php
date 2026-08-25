<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Readers;

use Generator;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use VmEngine\Synapse\Services\Excel\Contracts\ReaderInterface;

class SpoutReader implements ReaderInterface
{
    /**
     * openspout's CsvReader/XlsxReader don't close AbstractReader's generic
     * template parameter, so PHPStan can't verify them against a fully
     * generic ReaderInterface type — use the concrete union instead.
     */
    private CsvReader|XlsxReader|null $reader = null;

    private string $filePath = '';

    /**
     * Open the file for reading.
     */
    public function open(string $filePath): void
    {
        $this->filePath = $filePath;
        $this->reader = $this->createReader($filePath);
        $this->reader->open($filePath);
    }

    /**
     * Read all rows from the file as a generator.
     *
     * @return Generator<int, array<int, mixed>>
     */
    public function rows(int $sheetIndex = 0): Generator
    {
        if ($this->reader === null) {
            throw new RuntimeException('Reader not opened. Call open() first.');
        }

        $currentSheet = 0;

        foreach ($this->reader->getSheetIterator() as $sheet) {
            if ($currentSheet !== $sheetIndex) {
                $currentSheet++;

                continue;
            }

            $rowNumber = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $rowNumber++;
                $cells = array_map(
                    fn (Cell $cell) => $cell->getValue(),
                    $row->getCells()
                );

                yield $rowNumber => $cells;
            }

            break;
        }
    }

    /**
     * Close the reader and free resources.
     */
    public function close(): void
    {
        if ($this->reader !== null) {
            $this->reader->close();
            $this->reader = null;
        }
    }

    /**
     * Create the appropriate reader based on file extension.
     *
     * openspout's CsvReader/XlsxReader don't close AbstractReader's generic
     * template parameter, so PHPStan can't verify them against a fully
     * generic ReaderInterface return type — return the concrete union instead.
     */
    private function createReader(string $filePath): CsvReader|XlsxReader
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'tsv' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => throw new RuntimeException("Unsupported file type: .{$extension}. Supported: .csv, .tsv, .xlsx"),
        };
    }
}
