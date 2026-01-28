<?php

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use VmEngine\Synapse\Services\Excel\Writers\StyledWriter;

describe('StyledWriter', function () {
    beforeEach(function () {
        $this->outputDir = sys_get_temp_dir().'/synapse_styled_writer_test_'.uniqid();
        mkdir($this->outputDir, 0755, true);
    });

    afterEach(function () {
        if (is_dir($this->outputDir)) {
            $files = glob($this->outputDir.'/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->outputDir);
        }
    });

    it('creates new spreadsheet and writes data', function () {
        $outputPath = $this->outputDir.'/output.xlsx';

        $writer = new StyledWriter;
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Email']);
        $writer->writeRow(['John Doe', 'john@example.com']);
        $writer->writeRow(['Jane Smith', 'jane@example.com']);
        $writer->close();

        expect(file_exists($outputPath))->toBeTrue();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('Name');
        expect($sheet->getCell('B1')->getValue())->toBe('Email');
        expect($sheet->getCell('A2')->getValue())->toBe('John Doe');
        expect($sheet->getCell('A3')->getValue())->toBe('Jane Smith');

        $spreadsheet->disconnectWorksheets();
    });

    it('loads template and writes data at starting row', function () {
        $templatePath = __DIR__.'/../../../fixtures/template.xlsx';
        $outputPath = $this->outputDir.'/templated_output.xlsx';

        $writer = new StyledWriter;
        $writer->setTemplatePath($templatePath);
        $writer->setStartingRow(5);
        $writer->open($outputPath);
        $writer->writeRow(['John Doe', 'john@example.com', '123']);
        $writer->writeRow(['Jane Smith', 'jane@example.com', '456']);
        $writer->close();

        expect(file_exists($outputPath))->toBeTrue();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Template header should be preserved
        expect($sheet->getCell('A1')->getValue())->toBe('Company Report');

        // Data should start at row 5
        expect($sheet->getCell('A5')->getValue())->toBe('John Doe');
        expect($sheet->getCell('A6')->getValue())->toBe('Jane Smith');

        $spreadsheet->disconnectWorksheets();
    });

    it('applies column widths', function () {
        $outputPath = $this->outputDir.'/column_width.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Name', 'Email']);
        $writer->setColumnStyles([
            'Name' => ['width' => 30],
            'Email' => ['width' => 50],
        ]);
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Email']);
        $writer->writeRow(['John', 'john@example.com']);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getColumnDimension('A')->getWidth())->toBe(30.0);
        expect($sheet->getColumnDimension('B')->getWidth())->toBe(50.0);

        $spreadsheet->disconnectWorksheets();
    });

    it('applies autoSize to columns', function () {
        $outputPath = $this->outputDir.'/auto_size.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Name', 'Email']);
        $writer->setColumnStyles([
            'Name' => ['autoSize' => true],
        ]);
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Email']);
        $writer->writeRow(['John Doe with a very long name', 'john@example.com']);
        $writer->close();

        // Note: autoSize is calculated at save time. After reload, it's stored as
        // a computed width, not a flag. Verify the width was auto-calculated
        // (should be > default width of 8.43 for a long name)
        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Column A with autoSize should have a calculated width > default
        $colAWidth = $sheet->getColumnDimension('A')->getWidth();
        expect($colAWidth)->toBeGreaterThan(8.43);

        $spreadsheet->disconnectWorksheets();
    });

    it('applies header style to header row', function () {
        $outputPath = $this->outputDir.'/header_style.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Name', 'Email']);
        $writer->setHeaderStyle([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4472C4'],
            ],
        ]);
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Email']);
        $writer->writeRow(['John', 'john@example.com']);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('B1')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('4472C4');

        // Data row should not have the header style
        expect($sheet->getStyle('A2')->getFont()->getBold())->toBeFalse();

        $spreadsheet->disconnectWorksheets();
    });

    it('applies column styles to data cells', function () {
        $outputPath = $this->outputDir.'/column_styles.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Name', 'Salary']);
        $writer->setColumnStyles([
            'Name' => ['font' => ['bold' => true]],
            'Salary' => [
                'numberFormat' => ['formatCode' => '#,##0.00'],
                'alignment' => ['horizontal' => 'right'],
            ],
        ]);
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Salary']);
        $writer->writeRow(['John', 50000]);
        $writer->writeRow(['Jane', 60000]);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Data cells should have styles applied
        expect($sheet->getStyle('A2')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('A3')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('B2')->getNumberFormat()->getFormatCode())->toBe('#,##0.00');
        expect($sheet->getStyle('B2')->getAlignment()->getHorizontal())->toBe('right');

        $spreadsheet->disconnectWorksheets();
    });

    it('freezes header row by default', function () {
        $outputPath = $this->outputDir.'/freeze_pane.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Name', 'Email']);
        $writer->setHeaderStyle(['font' => ['bold' => true]]);
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Email']);
        $writer->writeRow(['John', 'john@example.com']);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getFreezePane())->toBe('A2');

        $spreadsheet->disconnectWorksheets();
    });

    it('can disable header row freeze', function () {
        $outputPath = $this->outputDir.'/no_freeze.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Name', 'Email']);
        $writer->setHeaderStyle(['font' => ['bold' => true]]);
        $writer->setFreezeHeaderRow(false);
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Email']);
        $writer->writeRow(['John', 'john@example.com']);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getFreezePane())->toBeNull();

        $spreadsheet->disconnectWorksheets();
    });

    it('throws exception when writing to unopened writer', function () {
        $writer = new StyledWriter;
        $writer->writeRow(['data']);
    })->throws(RuntimeException::class, 'Writer not opened');

    it('throws exception for CSV output with styles', function () {
        $writer = new StyledWriter;
        $writer->open($this->outputDir.'/output.csv');
    })->throws(RuntimeException::class, 'StyledWriter only supports XLSX format');

    it('handles empty styles gracefully', function () {
        $outputPath = $this->outputDir.'/empty_styles.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Name', 'Email']);
        $writer->setColumnStyles([]);
        $writer->setHeaderStyle([]);
        $writer->open($outputPath);
        $writer->writeRow(['Name', 'Email']);
        $writer->writeRow(['John', 'john@example.com']);
        $writer->close();

        expect(file_exists($outputPath))->toBeTrue();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('Name');
        expect($sheet->getCell('A2')->getValue())->toBe('John');

        $spreadsheet->disconnectWorksheets();
    });

    it('applies number format correctly', function () {
        $outputPath = $this->outputDir.'/number_format.xlsx';

        $writer = new StyledWriter;
        $writer->setHeaderLabels(['Amount', 'Date']);
        $writer->setColumnStyles([
            'Amount' => ['numberFormat' => ['formatCode' => '$#,##0.00']],
            'Date' => ['numberFormat' => ['formatCode' => 'yyyy-mm-dd']],
        ]);
        $writer->open($outputPath);
        $writer->writeRow(['Amount', 'Date']);
        $writer->writeRow([1234.56, '2024-01-15']);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getStyle('A2')->getNumberFormat()->getFormatCode())->toBe('$#,##0.00');
        expect($sheet->getStyle('B2')->getNumberFormat()->getFormatCode())->toBe('yyyy-mm-dd');

        $spreadsheet->disconnectWorksheets();
    });

    it('sets sheet name correctly', function () {
        $outputPath = $this->outputDir.'/sheet_name.xlsx';

        $writer = new StyledWriter;
        $writer->open($outputPath, 'Custom Sheet');
        $writer->writeRow(['Name']);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        expect($spreadsheet->getActiveSheet()->getTitle())->toBe('Custom Sheet');
        $spreadsheet->disconnectWorksheets();
    });

    it('handles columns beyond Z (multi-letter columns)', function () {
        $outputPath = $this->outputDir.'/multi_letter.xlsx';

        // Create 30 columns (A-AD)
        $headers = [];
        $headerLabels = [];
        for ($i = 0; $i < 30; $i++) {
            $headers[] = "Col{$i}";
            $headerLabels[] = "Col{$i}";
        }

        $writer = new StyledWriter;
        $writer->setHeaderLabels($headerLabels);
        $writer->setColumnStyles([
            'Col27' => ['width' => 25], // This should be column AB
        ]);
        $writer->open($outputPath);
        $writer->writeRow($headers);
        $writer->close();

        $spreadsheet = IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getColumnDimension('AB')->getWidth())->toBe(25.0);
        expect($sheet->getCell('AB1')->getValue())->toBe('Col27');

        $spreadsheet->disconnectWorksheets();
    });
});
