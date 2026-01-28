<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Writers;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\WriterInterface as SpoutWriterInterface;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use VmEngine\Synapse\Services\Excel\Contracts\WriterInterface;

class SpoutWriter implements WriterInterface
{
    private ?SpoutWriterInterface $writer = null;

    /**
     * Open the writer for the given output path.
     */
    public function open(string $outputPath, ?string $sheetName = null): void
    {
        $this->writer = $this->createWriter($outputPath);
        $this->writer->openToFile($outputPath);

        if ($sheetName !== null && $this->writer instanceof XlsxWriter) {
            $this->writer->getCurrentSheet()->setName($sheetName);
        }
    }

    /**
     * Write a single row of data.
     *
     * @param  array<int|string, mixed>  $row
     */
    public function writeRow(array $row): void
    {
        if ($this->writer === null) {
            throw new RuntimeException('Writer not opened. Call open() first.');
        }

        $this->writer->addRow(Row::fromValues(array_values($row)));
    }

    /**
     * Close the writer and finalize the file.
     */
    public function close(): void
    {
        if ($this->writer !== null) {
            $this->writer->close();
            $this->writer = null;
        }
    }

    /**
     * Create the appropriate writer based on file extension.
     */
    private function createWriter(string $outputPath): SpoutWriterInterface
    {
        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'tsv' => new CsvWriter,
            'xlsx' => new XlsxWriter,
            default => throw new RuntimeException("Unsupported file type: .{$extension}. Supported: .csv, .tsv, .xlsx"),
        };
    }
}
