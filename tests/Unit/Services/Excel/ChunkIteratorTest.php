<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use VmEngine\Synapse\Services\Excel\Support\ChunkIterator;

describe('ChunkIterator', function () {
    it('processes rows in chunks of specified size', function () {
        $rows = createGenerator([
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
            ['name' => 'D'],
            ['name' => 'E'],
        ]);

        $chunks = [];

        $total = ChunkIterator::process($rows, 2, function (Collection $chunk, int $index) use (&$chunks) {
            $chunks[] = ['index' => $index, 'count' => $chunk->count(), 'data' => $chunk->toArray()];
        });

        expect($total)->toBe(5);
        expect($chunks)->toHaveCount(3);
        expect($chunks[0]['index'])->toBe(0);
        expect($chunks[0]['count'])->toBe(2);
        expect($chunks[1]['index'])->toBe(1);
        expect($chunks[1]['count'])->toBe(2);
        expect($chunks[2]['index'])->toBe(2);
        expect($chunks[2]['count'])->toBe(1);
    });

    it('handles single chunk when data fits', function () {
        $rows = createGenerator([
            ['name' => 'A'],
            ['name' => 'B'],
        ]);

        $chunks = [];

        $total = ChunkIterator::process($rows, 10, function (Collection $chunk, int $index) use (&$chunks) {
            $chunks[] = ['index' => $index, 'count' => $chunk->count()];
        });

        expect($total)->toBe(2);
        expect($chunks)->toHaveCount(1);
        expect($chunks[0]['index'])->toBe(0);
        expect($chunks[0]['count'])->toBe(2);
    });

    it('handles empty generator', function () {
        $rows = createGenerator([]);

        $chunks = [];

        $total = ChunkIterator::process($rows, 10, function (Collection $chunk, int $index) use (&$chunks) {
            $chunks[] = $index;
        });

        expect($total)->toBe(0);
        expect($chunks)->toHaveCount(0);
    });

    it('skips specified number of rows', function () {
        $rows = createGenerator([
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
            ['name' => 'D'],
            ['name' => 'E'],
        ]);

        $collected = [];

        $total = ChunkIterator::process($rows, 10, function (Collection $chunk) use (&$collected) {
            $collected = $chunk->toArray();
        }, skipRows: 2);

        expect($total)->toBe(3);
        expect($collected)->toHaveCount(3);
        expect($collected[0]['name'])->toBe('C');
    });

    it('handles exact chunk size', function () {
        $rows = createGenerator([
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
            ['name' => 'D'],
        ]);

        $chunks = [];

        $total = ChunkIterator::process($rows, 2, function (Collection $chunk, int $index) use (&$chunks) {
            $chunks[] = $chunk->count();
        });

        expect($total)->toBe(4);
        expect($chunks)->toHaveCount(2);
        expect($chunks[0])->toBe(2);
        expect($chunks[1])->toBe(2);
    });

    it('processes single row', function () {
        $rows = createGenerator([
            ['name' => 'A'],
        ]);

        $chunks = [];

        $total = ChunkIterator::process($rows, 5, function (Collection $chunk, int $index) use (&$chunks) {
            $chunks[] = $chunk->count();
        });

        expect($total)->toBe(1);
        expect($chunks)->toHaveCount(1);
    });
});

/**
 * Helper to create a generator from an array.
 *
 * @param  array<int, array<string, mixed>>  $data
 * @return Generator<int, array<string, mixed>>
 */
function createGenerator(array $data): Generator
{
    foreach ($data as $row) {
        yield $row;
    }
}
