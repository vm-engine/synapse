<?php

declare(strict_types=1);

use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use VmEngine\Synapse\Services\Excel\ExcelExporter;
use VmEngine\Synapse\Services\Excel\Readers\SpoutReader;
use VmEngine\Synapse\Services\Excel\Transformers\BooleanTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\DateFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\NumberFormatTransformer;
use VmEngine\Synapse\Services\Excel\Transformers\UpperCaseTransformer;

describe('ExcelExporter', function () {
    beforeEach(function () {
        $this->outputDir = sys_get_temp_dir().'/synapse_test_'.uniqid();
        mkdir($this->outputDir, 0755, true);
    });

    afterEach(function () {
        // Clean up output directory
        if (is_dir($this->outputDir)) {
            $files = glob($this->outputDir.'/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->outputDir);
        }
    });

    it('exports CSV data from a query builder', function () {
        // First import data to have something to export
        $outputPath = $this->outputDir.'/output.csv';

        // Use a mock query that returns standard objects
        $items = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com'],
            (object) ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns(['name', 'email'])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        // Read back and verify
        $reader = new SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows)->toHaveCount(3); // header + 2 data rows
        expect($rows[0][0])->toBe('name');
        expect($rows[0][1])->toBe('email');
        expect($rows[1][0])->toBe('John Doe');
        expect($rows[2][0])->toBe('Jane Smith');
    });

    it('exports XLSX data with custom headers', function () {
        $outputPath = $this->outputDir.'/output.xlsx';

        $items = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
            (object) ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Full Name' => 'name',
                'Email Address' => 'email',
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        // Read back and verify
        $reader = new SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows)->toHaveCount(3); // header + 2 data rows
        expect($rows[0][0])->toBe('Full Name');
        expect($rows[0][1])->toBe('Email Address');
        expect($rows[1][0])->toBe('John Doe');
    });

    it('exports XLSX data with closure columns', function () {
        $outputPath = $this->outputDir.'/output.xlsx';

        $items = collect([
            (object) ['first_name' => 'John', 'last_name' => 'Doe'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Full Name' => fn ($record) => $record->first_name.' '.$record->last_name,
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        $reader = new SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows[1][0])->toBe('John Doe');
    });

    it('exports with template using PhpSpreadsheet', function () {
        $templatePath = __DIR__.'/../../../fixtures/template.xlsx';
        $outputPath = $this->outputDir.'/templated_output.xlsx';

        $items = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '123'],
            (object) ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '456'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns(['name', 'email', 'phone'])
            ->startingRow(5)
            ->template($templatePath)
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        // Verify template content is preserved using PhpSpreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Template header should be preserved
        expect($sheet->getCell('A1')->getValue())->toBe('Company Report');
        expect($sheet->getCell('A4')->getValue())->toBe('Name');

        // Data should start at row 5
        expect($sheet->getCell('A5')->getValue())->toBe('John Doe');
        expect($sheet->getCell('B5')->getValue())->toBe('john@example.com');
        expect($sheet->getCell('A6')->getValue())->toBe('Jane Smith');

        $spreadsheet->disconnectWorksheets();
    });

    it('exports with sheet name', function () {
        $outputPath = $this->outputDir.'/sheet_output.xlsx';

        $items = collect([
            (object) ['name' => 'John'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns(['name'])
            ->sheet('Users Report')
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        // Verify sheet name
        $reader = new XlsxReader;
        $reader->open($outputPath);
        foreach ($reader->getSheetIterator() as $sheet) {
            expect($sheet->getName())->toBe('Users Report');
            break;
        }
        $reader->close();
    });

    it('creates output directory if it does not exist', function () {
        $nestedDir = $this->outputDir.'/nested/deep/dir';
        $outputPath = $nestedDir.'/output.csv';

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator([]));

        ExcelExporter::make($mockQuery)
            ->columns(['name'])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        // Clean up nested dirs
        unlink($outputPath);
        rmdir($nestedDir);
        rmdir(dirname($nestedDir));
        rmdir(dirname(dirname($nestedDir)));
    });

    it('throws exception when ExportQuery class does not implement interface', function () {
        ExcelExporter::make(\stdClass::class);
    })->throws(RuntimeException::class, 'must implement');

    it('throws exception when dispatching with query builder instead of class', function () {
        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);

        ExcelExporter::make($mockQuery)->dispatch('/tmp/output.xlsx');
    })->throws(RuntimeException::class, 'Background export requires an ExportQuery class');

    it('exports XLSX with column styles', function () {
        $outputPath = $this->outputDir.'/styled_columns.xlsx';

        $items = collect([
            (object) ['name' => 'John Doe', 'salary' => 50000],
            (object) ['name' => 'Jane Smith', 'salary' => 60000],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Full Name' => 'name',
                'Salary' => 'salary',
            ])
            ->columnStyles([
                'Full Name' => ['width' => 30],
                'Salary' => [
                    'numberFormat' => ['formatCode' => '#,##0.00'],
                    'width' => 15,
                ],
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getColumnDimension('A')->getWidth())->toBe(30.0);
        expect($sheet->getColumnDimension('B')->getWidth())->toBe(15.0);
        expect($sheet->getStyle('B2')->getNumberFormat()->getFormatCode())->toBe('#,##0.00');

        $spreadsheet->disconnectWorksheets();
    });

    it('exports XLSX with header style', function () {
        $outputPath = $this->outputDir.'/styled_header.xlsx';

        $items = collect([
            (object) ['name' => 'John Doe'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns(['Name' => 'name'])
            ->headerStyle([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('4472C4');

        $spreadsheet->disconnectWorksheets();
    });

    it('exports XLSX with both column and header styles', function () {
        $outputPath = $this->outputDir.'/fully_styled.xlsx';

        $items = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Full Name' => 'name',
                'Email' => 'email',
            ])
            ->columnStyles([
                'Full Name' => ['width' => 25, 'font' => ['bold' => true]],
                'Email' => ['width' => 40],
            ])
            ->headerStyle([
                'font' => ['bold' => true],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '333333']],
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Header should have both header style and column width
        expect($sheet->getStyle('A1')->getFont()->getBold())->toBeTrue();
        expect($sheet->getStyle('A1')->getFill()->getStartColor()->getRGB())->toBe('333333');
        expect($sheet->getColumnDimension('A')->getWidth())->toBe(25.0);

        // Data cell should have column style (bold for name column)
        expect($sheet->getStyle('A2')->getFont()->getBold())->toBeTrue();

        // Freeze pane should be applied
        expect($sheet->getFreezePane())->toBe('A2');

        $spreadsheet->disconnectWorksheets();
    });

    it('exports with template and styles combined', function () {
        $templatePath = __DIR__.'/../../../fixtures/template.xlsx';
        $outputPath = $this->outputDir.'/template_styled.xlsx';

        $items = collect([
            (object) ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '123'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns(['name', 'email', 'phone'])
            ->startingRow(5)
            ->template($templatePath)
            ->columnStyles([
                'name' => ['font' => ['bold' => true]],
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($outputPath);
        $sheet = $spreadsheet->getActiveSheet();

        // Template header preserved
        expect($sheet->getCell('A1')->getValue())->toBe('Company Report');

        // Data at starting row with style
        expect($sheet->getCell('A5')->getValue())->toBe('John Doe');
        expect($sheet->getStyle('A5')->getFont()->getBold())->toBeTrue();

        $spreadsheet->disconnectWorksheets();
    });

    it('falls back to SpoutWriter when no styles are set', function () {
        $outputPath = $this->outputDir.'/no_styles.xlsx';

        $items = collect([
            (object) ['name' => 'John Doe'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        // Export without any styles - should use SpoutWriter
        ExcelExporter::make($mockQuery)
            ->columns(['name'])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        // Read back to verify data was written
        $reader = new VmEngine\Synapse\Services\Excel\Readers\SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows)->toHaveCount(2);
        expect($rows[0][0])->toBe('name');
        expect($rows[1][0])->toBe('John Doe');
    });

    it('throws exception when applying styles to CSV output', function () {
        $outputPath = $this->outputDir.'/styled.csv';

        $items = collect([
            (object) ['name' => 'John Doe'],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns(['name'])
            ->columnStyles(['name' => ['width' => 30]])
            ->export($outputPath);
    })->throws(RuntimeException::class, 'not supported for CSV exports');

    it('exports with dot notation for nested objects', function () {
        $outputPath = $this->outputDir.'/dot_notation.csv';

        $items = collect([
            (object) [
                'name' => 'Invoice #1',
                'client' => (object) [
                    'name' => 'Acme Corp',
                    'company' => (object) ['name' => 'Acme Holdings'],
                ],
            ],
            (object) [
                'name' => 'Invoice #2',
                'client' => (object) [
                    'name' => 'Widget Co',
                    'company' => (object) ['name' => 'Widget Holdings'],
                ],
            ],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Invoice' => 'name',
                'Client' => 'client.name',
                'Company' => 'client.company.name',
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        $reader = new SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows)->toHaveCount(3);
        expect($rows[0])->toBe(['Invoice', 'Client', 'Company']);
        expect($rows[1])->toBe(['Invoice #1', 'Acme Corp', 'Acme Holdings']);
        expect($rows[2])->toBe(['Invoice #2', 'Widget Co', 'Widget Holdings']);
    });

    it('exports with column transformers', function () {
        $outputPath = $this->outputDir.'/transformers.csv';

        $items = collect([
            (object) [
                'name' => 'john doe',
                'salary' => 50000.5,
                'is_active' => true,
                'created_at' => new \DateTimeImmutable('2025-06-15 14:30:00'),
            ],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Name' => [
                    'field' => 'name',
                    'transformer' => UpperCaseTransformer::class,
                ],
                'Salary' => [
                    'field' => 'salary',
                    'transformer' => NumberFormatTransformer::class,
                    'config' => ['decimals' => 2],
                ],
                'Active' => [
                    'field' => 'is_active',
                    'transformer' => BooleanTransformer::class,
                ],
                'Joined' => [
                    'field' => 'created_at',
                    'transformer' => DateFormatTransformer::class,
                    'config' => ['format' => 'd/m/Y'],
                ],
            ])
            ->export($outputPath);

        expect(file_exists($outputPath))->toBeTrue();

        $reader = new SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows)->toHaveCount(2);
        expect($rows[0])->toBe(['Name', 'Salary', 'Active', 'Joined']);
        expect($rows[1][0])->toBe('JOHN DOE');
        expect($rows[1][1])->toBe('50,000.50');
        expect($rows[1][2])->toBe('Yes');
        expect($rows[1][3])->toBe('15/06/2025');
    });

    it('exports with transformer using dot notation field', function () {
        $outputPath = $this->outputDir.'/transformer_dot.csv';

        $items = collect([
            (object) [
                'name' => 'John',
                'profile' => (object) ['status' => true],
            ],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Name' => 'name',
                'Status' => [
                    'field' => 'profile.status',
                    'transformer' => BooleanTransformer::class,
                    'config' => ['trueLabel' => 'Active', 'falseLabel' => 'Inactive'],
                ],
            ])
            ->export($outputPath);

        $reader = new SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows[1][1])->toBe('Active');
    });

    it('handles transformer array without transformer class as field-only extraction', function () {
        $outputPath = $this->outputDir.'/array_no_transformer.csv';

        $items = collect([
            (object) ['client' => (object) ['name' => 'Acme']],
        ]);

        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $mockQuery->shouldReceive('cursor')->andReturn(new \ArrayIterator($items->toArray()));

        ExcelExporter::make($mockQuery)
            ->columns([
                'Client' => ['field' => 'client.name'],
            ])
            ->export($outputPath);

        $reader = new SpoutReader;
        $reader->open($outputPath);
        $rows = [];
        foreach ($reader->rows() as $row) {
            $rows[] = $row;
        }
        $reader->close();

        expect($rows[1][0])->toBe('Acme');
    });

    it('serializes transformer array columns for background dispatch', function () {
        $mockQuery = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);

        $columns = [
            'Name' => 'name',
            'Notes' => fn ($r) => $r->notes ?? 'None',
            'Active' => [
                'field' => 'is_active',
                'transformer' => BooleanTransformer::class,
            ],
        ];

        // Use reflection to test serializeColumns
        $exporter = ExcelExporter::make($mockQuery)->columns($columns);
        $method = new \ReflectionMethod($exporter, 'serializeColumns');

        $serialized = $method->invoke($exporter);

        expect($serialized['Name'])->toBe('name');
        expect($serialized['Notes'])->toBeNull(); // Closure serialized as null
        expect($serialized['Active'])->toBe([
            'field' => 'is_active',
            'transformer' => BooleanTransformer::class,
        ]);
    });
});
