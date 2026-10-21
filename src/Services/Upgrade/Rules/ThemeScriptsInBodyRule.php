<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade\Rules;

use VmEngine\Synapse\Services\Upgrade\AbstractUpgradeRule;

/**
 * Flags `@themeScripts(...)` rendered after </head> (theme layouts copied before v3.6.1). On
 * wire:navigate from another layout Livewire waits only for new <head> scripts before starting
 * Alpine, so a layout entry in <body> registers its Alpine components too late ("Undefined
 * variable: frontendHeader"). Move it into <head>, right after @themeStyles('<layout>').
 *
 * Not auto-fixable: layouts are hand-edited copies; moving lines (and their comments) is left
 * to the developer. Detect-only.
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php (rule F).
 */
class ThemeScriptsInBodyRule extends AbstractUpgradeRule
{
    private const PATTERN = '/@themeScripts\s*\(/';

    public function id(): string
    {
        return 'F';
    }

    public function category(): string
    {
        return 'theme-scripts-in-body';
    }

    public function label(): string
    {
        return "@themeScripts(...) after </head> (layouts copied before v3.6.1) — move it into <head>, right after @themeStyles(...), so wire:navigate waits for the layout's Alpine components";
    }

    public function isAutoFixable(): bool
    {
        return false;
    }

    public function extensions(): array
    {
        return ['blade.php'];
    }

    public function matchesLine(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function matchingLines(array $lines): array
    {
        $headEnd = null;

        foreach ($lines as $index => $line) {
            if (str_contains($line, '</head>')) {
                $headEnd = $index;

                break;
            }
        }

        if ($headEnd === null) {
            return [];
        }

        return array_values(array_filter(
            array_keys(array_filter($lines, fn (string $line): bool => $this->matchesLine($line))),
            fn (int $index): bool => $index > $headEnd,
        ));
    }
}
