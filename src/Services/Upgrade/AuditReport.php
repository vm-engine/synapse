<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade;

final class AuditReport
{
    /**
     * @param  array<int, UpgradeFinding>  $findings
     */
    public function __construct(
        public readonly string $installedVersion,
        public readonly array $findings,
    ) {}

    /**
     * @return array<int, UpgradeFinding>
     */
    public function autoFixable(): array
    {
        return array_values(array_filter($this->findings, fn (UpgradeFinding $f) => $f->autoFixable));
    }

    /**
     * @return array<int, UpgradeFinding>
     */
    public function manualReview(): array
    {
        return array_values(array_filter($this->findings, fn (UpgradeFinding $f) => ! $f->autoFixable));
    }

    /**
     * @return array<int, string> distinct absolute/relative file paths containing auto-fixable findings
     */
    public function autoFixableFiles(): array
    {
        return array_values(array_unique(array_map(
            fn (UpgradeFinding $f) => $f->file,
            $this->autoFixable()
        )));
    }

    public function toMarkdown(): string
    {
        return (new MarkdownReportBuilder)->build($this);
    }
}
