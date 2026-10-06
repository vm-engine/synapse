<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade;

final class ApplyReport
{
    /**
     * @param  array<int, string>  $appliedFiles  files successfully rewritten
     * @param  array<int, string>  $backedUpFiles  .bak files written before rewriting
     * @param  array<string, string>  $failedFiles  file => error message
     */
    public function __construct(
        public readonly array $appliedFiles,
        public readonly array $backedUpFiles,
        public readonly array $failedFiles,
    ) {}

    public function appliedCount(): int
    {
        return count($this->appliedFiles);
    }

    public function failedCount(): int
    {
        return count($this->failedFiles);
    }
}
