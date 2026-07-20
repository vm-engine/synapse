<?php

namespace VmEngine\Synapse\Services\Upgrade;

final class MarkdownReportBuilder
{
    public function build(AuditReport $report): string
    {
        $lines = [];
        $lines[] = '# Synapse Upgrade Audit';
        $lines[] = '';
        $lines[] = 'Generated: '.date('Y-m-d H:i:s');
        $lines[] = 'Detected `vm-engine/synapse` version: '.$report->installedVersion;
        $lines[] = '';

        if ($report->findings === []) {
            $lines[] = 'No legacy patterns detected. ✓';
            $lines[] = '';

            return implode("\n", $lines);
        }

        $lines[] = '## Breaking changes found (auto-fixable)';
        $lines[] = '';
        $lines = array_merge($lines, $this->renderSection($report->autoFixable()));

        $lines[] = '## Manual review required';
        $lines[] = '';
        $lines = array_merge($lines, $this->renderSection($report->manualReview()));

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, UpgradeFinding>  $findings
     * @return array<int, string>
     */
    private function renderSection(array $findings): array
    {
        if ($findings === []) {
            return ['_None found._', ''];
        }

        $byCategory = [];
        foreach ($findings as $finding) {
            $byCategory[$finding->category][] = $finding;
        }

        $lines = [];

        foreach ($byCategory as $category => $categoryFindings) {
            $lines[] = '### '.$category.' ('.$categoryFindings[0]->ruleId.')';
            $lines[] = '';
            $lines[] = $categoryFindings[0]->label;
            $lines[] = '';

            foreach ($categoryFindings as $finding) {
                $lines[] = sprintf('- `%s:%d` — `%s`', $finding->file, $finding->line, $finding->snippet);
            }

            $lines[] = '';
        }

        return $lines;
    }
}
