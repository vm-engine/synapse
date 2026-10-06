<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade\Rules;

use VmEngine\Synapse\Services\Upgrade\AbstractUpgradeRule;

/**
 * Route::livewire('/todo', 'example.todo-form') (pre-v2.2.0) -> 'example::todo-form'
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php (rule B).
 */
class RouteLivewireNotationRule extends AbstractUpgradeRule
{
    private const PATTERN = '/(Route::livewire\(\s*[\'"][^\'"]*[\'"]\s*,\s*[\'"])([a-zA-Z0-9_-]+)\.(?!:)([a-zA-Z0-9_.-]+)([\'"])/';

    public function id(): string
    {
        return 'B';
    }

    public function category(): string
    {
        return 'route-livewire-notation';
    }

    public function label(): string
    {
        return "Route::livewire() dotted string target (pre-v2.2.0) — e.g. Route::livewire('/todo', 'example.todo-form') should use 'example::todo-form'";
    }

    public function isAutoFixable(): bool
    {
        return true;
    }

    public function extensions(): array
    {
        return ['php'];
    }

    public function matchesLine(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function fix(string $contents): string
    {
        return preg_replace(self::PATTERN, '$1$2::$3$4', $contents);
    }
}
