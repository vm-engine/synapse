<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade\Rules;

use VmEngine\Synapse\Services\Upgrade\AbstractUpgradeRule;

/**
 * <livewire:example.todo-form /> (pre-v2.2.0) -> <livewire:example::todo-form />
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php (rule A).
 */
class BladeTagNotationRule extends AbstractUpgradeRule
{
    private const PATTERN = '/<livewire:([a-zA-Z0-9_-]+)\.(?!:)([a-zA-Z0-9_.-]+)/';

    public function id(): string
    {
        return 'A';
    }

    public function category(): string
    {
        return 'blade-tag-notation';
    }

    public function label(): string
    {
        return 'Livewire Blade tag dot-notation (pre-v2.2.0) — e.g. <livewire:example.todo-form /> should be <livewire:example::todo-form />';
    }

    public function isAutoFixable(): bool
    {
        return true;
    }

    public function extensions(): array
    {
        return ['blade.php'];
    }

    public function matchesLine(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function fix(string $contents): string
    {
        return preg_replace(self::PATTERN, '<livewire:$1::$2', $contents);
    }
}
