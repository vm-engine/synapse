<?php

namespace VmEngine\Synapse\Services\Upgrade;

final class UpgradeFinding
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $snippet,
        public readonly string $ruleId,
        public readonly string $category,
        public readonly string $label,
        public readonly bool $autoFixable,
    ) {}
}
