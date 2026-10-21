<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade;

use LogicException;

abstract class AbstractUpgradeRule implements UpgradeRule
{
    public function matchingLines(array $lines): array
    {
        return array_keys(array_filter($lines, fn (string $line): bool => $this->matchesLine($line)));
    }

    public function fix(string $contents): string
    {
        throw new LogicException(sprintf('Rule "%s" is not auto-fixable.', $this->id()));
    }
}
