<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Writers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use VmEngine\Synapse\Services\Excel\Contracts\WriterInterface;

class StyledWriter implements WriterInterface
{
    private ?Spreadsheet $spreadsheet = null;

    private string $outputPath = '';

    private int $currentRow = 1;

    private ?string $sheetName = null;

    private int $startingRow = 1;

    private ?string $templatePath = null;

    /** @var array<string, array<string, mixed>> */
    private array $columnStyles = [];

    /** @var array<string, mixed> */
    private array $headerStyle = [];

    /** @var array<int, string> */
    private array $headerLabels = [];

    private bool $freezeHeaderRow = true;

    private int $headerRowIndex = 0;

    private int $dataStartRow = 0;

    private bool $headerWritten = false;

    /**
     * Set the row number where data starts (1-based).
     */
    public function setStartingRow(int $row): self
    {
        $this->startingRow = $row;

        return $this;
    }

    /**
     * Set the template path to load from.
     */
    public function setTemplatePath(?string $path): self
    {
        $this->templatePath = $path;

        return $this;
    }

    /**
     * Set column styles keyed by header label.
     *
     * @param  array<string, array<string, mixed>>  $styles
     */
    public function setColumnStyles(array $styles): self
    {
        $this->columnStyles = $styles;

        return $this;
    }

    /**
     * Set the header row style.
     *
     * @param  array<string, mixed>  $style
     */
    public function setHeaderStyle(array $style): self
    {
        $this->headerStyle = $style;

        return $this;
    }

    /**
     * Set header labels for column mapping.
     *
     * @param  array<int, string>  $labels
     */
    public function setHeaderLabels(array $labels): self
    {
        $this->headerLabels = $labels;

        return $this;
    }

    /**
     * Enable or disable header row freeze pane.
     */
    public function setFreezeHeaderRow(bool $freeze): self
    {
        $this->freezeHeaderRow = $freeze;

        return $this;
    }

    /**
     * Open the writer for the given output path.
     */
    public function open(string $outputPath, ?string $sheetName = null): void
    {
        if (! class_exists(Spreadsheet::class)) {
            throw new RuntimeException(
                'phpoffice/phpspreadsheet is required for styled exports. Install it with: composer require phpoffice/phpspreadsheet:^2.0'
            );
        }

        $extension = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            throw new RuntimeException(
                'StyledWriter only supports XLSX format. CSV does not support styling. Use SpoutWriter for CSV exports.'
            );
        }

        $this->outputPath = $outputPath;
        $this->sheetName = $sheetName;

        if ($this->templatePath !== null) {
            if (! file_exists($this->templatePath)) {
                throw new RuntimeException("Template file not found: {$this->templatePath}");
            }
            $this->spreadsheet = IOFactory::load($this->templatePath);
        } else {
            $this->spreadsheet = new Spreadsheet;
        }

        $this->currentRow = $this->startingRow;

        if ($sheetName !== null) {
            $sheet = $this->spreadsheet->getSheetByName($sheetName);
            if ($sheet !== null) {
                $this->spreadsheet->setActiveSheetIndex(
                    $this->spreadsheet->getIndex($sheet)
                );
            } else {
                $this->spreadsheet->getActiveSheet()->setTitle($sheetName);
            }
        }

        $this->applyColumnWidths();
    }

    /**
     * Write a single row of data.
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

        // Track header vs data rows
        if (! $this->headerWritten) {
            if ($this->templatePath === null) {
                $this->headerRowIndex = $this->currentRow;
                $this->headerWritten = true;
                $this->dataStartRow = $this->currentRow + 1;
            } else {
                // For templates, first row is data
                $this->dataStartRow = $this->currentRow;
                $this->headerWritten = true;
            }
        }

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
            $sheet = $this->spreadsheet->getActiveSheet();

            // Apply header style if set and not using template
            if (! empty($this->headerStyle) && $this->headerRowIndex > 0) {
                $this->applyHeaderStyle($sheet);
            }

            // Apply column styles to data range
            if (! empty($this->columnStyles) && $this->dataStartRow > 0) {
                $this->applyColumnDataStyles($sheet);
            }

            // Apply autoSize after data is written
            if (! empty($this->columnStyles)) {
                $this->applyAutoSize($sheet);
            }

            // Apply freeze pane
            if ($this->freezeHeaderRow && $this->headerRowIndex > 0) {
                $sheet->freezePane('A'.($this->headerRowIndex + 1));
            }

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

    /**
     * Apply column widths from columnStyles (called during open).
     */
    private function applyColumnWidths(): void
    {
        if ($this->spreadsheet === null || empty($this->columnStyles)) {
            return;
        }

        $sheet = $this->spreadsheet->getActiveSheet();

        foreach ($this->headerLabels as $colIndex => $label) {
            if (! isset($this->columnStyles[$label])) {
                continue;
            }

            $style = $this->columnStyles[$label];
            $columnLetter = $this->getColumnLetter($colIndex);

            if (isset($style['width'])) {
                $sheet->getColumnDimension($columnLetter)->setWidth((float) $style['width']);
            }
        }
    }

    /**
     * Apply autoSize to columns (called during close after data is written).
     */
    private function applyAutoSize(Worksheet $sheet): void
    {
        foreach ($this->headerLabels as $colIndex => $label) {
            if (! isset($this->columnStyles[$label])) {
                continue;
            }

            $style = $this->columnStyles[$label];

            if (isset($style['autoSize']) && $style['autoSize'] === true) {
                $columnLetter = $this->getColumnLetter($colIndex);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }
        }
    }

    /**
     * Apply header style to the header row.
     */
    private function applyHeaderStyle(Worksheet $sheet): void
    {
        if (empty($this->headerLabels)) {
            return;
        }

        $lastColumn = $this->getColumnLetter(count($this->headerLabels) - 1);
        $range = "A{$this->headerRowIndex}:{$lastColumn}{$this->headerRowIndex}";

        $sheet->getStyle($range)->applyFromArray($this->headerStyle);
    }

    /**
     * Apply column styles to data cells.
     */
    private function applyColumnDataStyles(Worksheet $sheet): void
    {
        $lastDataRow = $this->currentRow - 1;

        if ($lastDataRow < $this->dataStartRow) {
            return;
        }

        foreach ($this->headerLabels as $colIndex => $label) {
            if (! isset($this->columnStyles[$label])) {
                continue;
            }

            $style = $this->columnStyles[$label];
            $columnLetter = $this->getColumnLetter($colIndex);

            // Extract style properties (exclude width/autoSize which are applied separately)
            $cellStyle = array_diff_key($style, ['width' => true, 'autoSize' => true]);

            if (empty($cellStyle)) {
                continue;
            }

            $range = "{$columnLetter}{$this->dataStartRow}:{$columnLetter}{$lastDataRow}";
            $sheet->getStyle($range)->applyFromArray($cellStyle);
        }
    }

    /**
     * Convert 0-based column index to Excel column letter.
     */
    private function getColumnLetter(int $index): string
    {
        $letter = '';
        $index++;

        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
