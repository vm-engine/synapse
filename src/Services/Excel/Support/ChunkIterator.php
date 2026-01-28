<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Excel\Support;

use Closure;
use Generator;
use Illuminate\Support\Collection;

class ChunkIterator
{
    /**
     * Iterate over a generator in chunks and call the callback for each chunk.
     *
     * @param  Generator<int, array<string, mixed>>  $rows
     * @param  Closure(Collection<int, array<string, mixed>>, int): void  $callback
     * @param  int  $skipRows  Number of rows to skip from the beginning
     * @return int Total number of rows processed
     */
    public static function process(
        Generator $rows,
        int $chunkSize,
        Closure $callback,
        int $skipRows = 0,
    ): int {
        $chunk = [];
        $chunkIndex = 0;
        $totalProcessed = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if ($skipped < $skipRows) {
                $skipped++;

                continue;
            }

            $chunk[] = $row;
            $totalProcessed++;

            if (count($chunk) >= $chunkSize) {
                $callback(collect($chunk), $chunkIndex);
                $chunk = [];
                $chunkIndex++;
            }
        }

        // Process remaining rows
        if (! empty($chunk)) {
            $callback(collect($chunk), $chunkIndex);
        }

        return $totalProcessed;
    }
}
