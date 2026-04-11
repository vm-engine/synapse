<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Finder;

use Livewire\Compiler\Parser\MultiFileParser;
use Livewire\Finder\Finder;

/**
 * Patched Livewire Finder that fixes an inconsistency between Finder and MultiFileParser.
 *
 * Bug: Livewire's Finder uses str_contains($baseName, 'index') to determine the file
 * naming convention inside an MFC directory. This incorrectly treats any directory whose
 * name contains "index" as a substring (e.g. "todo-index") as using the index.* naming
 * convention, causing it to look for "index.php" instead of "todo-index.php".
 *
 * However, MultiFileParser always derives the file name from basename($path), so it
 * always expects "todo-index.php" for a directory named "todo-index".
 *
 * This override makes hasValidMultiFileComponentSource consistent with MultiFileParser
 * by always using basename($dir) (with emoji stripped) as the file base name.
 *
 * @see Finder::hasValidMultiFileComponentSource
 * @see MultiFileParser::parse
 */
class SynapseFinder extends Finder
{
    /**
     * Override to fix the str_contains($baseName, 'index') bug in Livewire's Finder.
     *
     * Uses the same file name derivation as MultiFileParser::parse() — basename($dir)
     * with emoji stripped — ensuring both discovery and compilation are consistent.
     */
    protected function hasValidMultiFileComponentSource(string $dir, string $fileBaseName): bool
    {
        $name = preg_replace('/\x{26A1}[\x{FE0E}\x{FE0F}]?/u', '', basename($dir));

        return file_exists($dir.'/'.$name.'.php')
            && file_exists($dir.'/'.$name.'.blade.php');
    }
}
