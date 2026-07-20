<?php

namespace VmEngine\Synapse\Services\Upgrade\Rules;

use VmEngine\Synapse\Services\Upgrade\AbstractUpgradeRule;

/**
 * Livewire.hook('commit'|'morph.updated'|'message.processed', ...) (Livewire v3 API,
 * removed in v2.0.0) -> Livewire.interceptMessage(...).
 *
 * Not auto-fixable: the v4 callback shape differs from the v3 hook signature and
 * requires manually porting the callback body. Detect-only.
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php (rule D).
 */
class JsLivewireHooksRule extends AbstractUpgradeRule
{
    private const PATTERN = '/Livewire\.hook\(\s*[\'"](commit|morph\.updated|message\.processed)[\'"]/';

    public function id(): string
    {
        return 'D';
    }

    public function category(): string
    {
        return 'js-livewire-hooks';
    }

    public function label(): string
    {
        return "Livewire.hook('commit'|'morph.updated'|'message.processed', ...) (Livewire v3 API, removed in v2.0.0) — migrate to Livewire.interceptMessage()";
    }

    public function isAutoFixable(): bool
    {
        return false;
    }

    public function extensions(): array
    {
        return ['js'];
    }

    public function matchesLine(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
