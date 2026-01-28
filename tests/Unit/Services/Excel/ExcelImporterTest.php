<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use VmEngine\Synapse\Services\Excel\ExcelImporter;

describe('ExcelImporter', function () {
    it('imports all rows from CSV file', function () {
        $filePath = __DIR__.'/../../../fixtures/sample.csv';

        $rows = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->mapColumns(['name', 'email', 'phone'])
            ->import();

        expect($rows)->toBeInstanceOf(Collection::class);
        expect($rows)->toHaveCount(3);
        expect($rows[0]['name'])->toBe('John Doe');
        expect($rows[0]['email'])->toBe('john@example.com');
        expect($rows[0]['phone'])->toBe('1234567890');
        expect($rows[2]['name'])->toBe('Bob Wilson');
    });

    it('imports all rows from XLSX file', function () {
        $filePath = __DIR__.'/../../../fixtures/sample.xlsx';

        $rows = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->mapColumns(['name', 'email', 'phone'])
            ->import();

        expect($rows)->toBeInstanceOf(Collection::class);
        expect($rows)->toHaveCount(3);
        expect($rows[0]['name'])->toBe('John Doe');
        expect($rows[0]['email'])->toBe('john@example.com');
    });

    it('imports without column mapping', function () {
        $filePath = __DIR__.'/../../../fixtures/sample.csv';

        $rows = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->import();

        expect($rows)->toHaveCount(3);
        // Without mapping, returns raw indexed arrays
        expect($rows[0][0])->toBe('John Doe');
        expect($rows[0][1])->toBe('john@example.com');
    });

    it('imports with associative column mapping', function () {
        $filePath = __DIR__.'/../../../fixtures/sample.csv';

        $rows = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->mapColumns(['name' => 0, 'email' => 1])
            ->import();

        expect($rows)->toHaveCount(3);
        expect($rows[0])->toHaveKey('name');
        expect($rows[0])->toHaveKey('email');
        expect($rows[0])->not->toHaveKey('phone');
    });

    it('imports with closure mapping', function () {
        $filePath = __DIR__.'/../../../fixtures/sample.csv';

        $rows = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->mapColumns(fn (array $row, int $i) => [
                'full_name' => strtoupper($row[0]),
                'contact' => $row[1],
            ])
            ->import();

        expect($rows)->toHaveCount(3);
        expect($rows[0]['full_name'])->toBe('JOHN DOE');
        expect($rows[0]['contact'])->toBe('john@example.com');
    });

    it('imports including header row when startingRow is 1', function () {
        $filePath = __DIR__.'/../../../fixtures/sample.csv';

        $rows = ExcelImporter::make($filePath)
            ->startingRow(1)
            ->import();

        expect($rows)->toHaveCount(4); // header + 3 data rows
        expect($rows[0][0])->toBe('name');
    });

    it('processes CSV in chunks', function () {
        $filePath = __DIR__.'/../../../fixtures/sample.csv';

        $chunks = [];

        $total = ExcelImporter::make($filePath)
            ->startingRow(2)
            ->mapColumns(['name', 'email', 'phone'])
            ->chunk(2, function (Collection $rows, int $chunkIndex) use (&$chunks) {
                $chunks[] = [
                    'index' => $chunkIndex,
                    'count' => $rows->count(),
                    'first_name' => $rows->first()['name'],
                ];
            });

        expect($total)->toBe(3);
        expect($chunks)->toHaveCount(2);
        expect($chunks[0]['count'])->toBe(2);
        expect($chunks[0]['first_name'])->toBe('John Doe');
        expect($chunks[1]['count'])->toBe(1);
        expect($chunks[1]['first_name'])->toBe('Bob Wilson');
    });

    it('throws exception for unsupported file type', function () {
        ExcelImporter::make('/tmp/file.pdf')->import();
    })->throws(RuntimeException::class, 'Unsupported file type');
});
