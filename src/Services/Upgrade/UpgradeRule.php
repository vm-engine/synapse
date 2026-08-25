<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade;

interface UpgradeRule
{
    public function id(): string;

    public function category(): string;

    public function label(): string;

    public function isAutoFixable(): bool;

    /**
     * File extensions (without leading dot) this rule applies to, e.g. ['blade.php'].
     *
     * @return array<int, string>
     */
    public function extensions(): array;

    public function matchesLine(string $line): bool;

    /**
     * Rewrite the full file contents to the fixed form. Only called for
     * rules where isAutoFixable() is true.
     */
    public function fix(string $contents): string;
}
