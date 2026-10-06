<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Writers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;
use VmEngine\Synapse\Services\Excel\Contracts\WriterInterface;

class TemplateWriter implements WriterInterface
{
    private ?Spreadsheet $spreadsheet = null;

    private string $outputPath = '';

    private int $currentRow = 1;

    public function __construct(
        private readonly string $templatePath,
        private readonly int $startingRow = 1,
    ) {}

    /**
     * Open the writer by loading the template file.
     */
    public function open(string $outputPath, ?string $sheetName = null): void
    {
        if (! class_exists(Spreadsheet::class)) {
            throw new RuntimeException(
                'phpoffice/phpspreadsheet is required for template exports. Install it with: composer require phpoffice/phpspreadsheet:^2.0'
            );
        }

        if (! file_exists($this->templatePath)) {
            throw new RuntimeException("Template file not found: {$this->templatePath}");
        }

        $this->outputPath = $outputPath;
        $this->spreadsheet = IOFactory::load($this->templatePath);
        $this->currentRow = $this->startingRow;

        if ($sheetName !== null) {
            $sheet = $this->spreadsheet->getSheetByName($sheetName);
            if ($sheet !== null) {
                $this->spreadsheet->setActiveSheetIndex(
                    $this->spreadsheet->getIndex($sheet)
                );
            }
        }
    }

    /**
     * Write a single row of data into the template.
     *
     * @param  array<int|string, mixed>  $row
     */
    public function writeRow(array $row): void
    {
        if ($this->spreadsheet === null) {
            throw new RuntimeException('Writer not opened. Call open() first.');
        }

        $sheet = $this->spreadsheet->getActiveSheet();
        $values = array_values($row);

        foreach ($values as $colIndex => $value) {
            $sheet->setCellValue([$colIndex + 1, $this->currentRow], $value);
        }

        $this->currentRow++;
    }

    /**
     * Close the writer and save the file.
     */
    public function close(): void
    {
        if ($this->spreadsheet !== null) {
            $outputDir = dirname($this->outputPath);
            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
            $writer->save($this->outputPath);
            $this->spreadsheet->disconnectWorksheets();
            $this->spreadsheet = null;
        }
    }
}
