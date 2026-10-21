<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade\Rules;

use VmEngine\Synapse\Services\Upgrade\AbstractUpgradeRule;

/**
 * Alpine components registered in an anonymous alpine:init callback (theme JS, layouts and module
 * views copied before v3.6.2). alpine:init fires once per full page load; after a wire:navigate from
 * another layout the code runs with Alpine already started, the callback never fires and the page
 * logs "Undefined variable: <component>". Register with a named function instead:
 * `if (window.Alpine) { register(); } else { document.addEventListener('alpine:init', register); }`.
 *
 * Not auto-fixable: the callback body must be moved into a named function. Detect-only.
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php (rule G).
 */
class AlpineInitOnlyRule extends AbstractUpgradeRule
{
    private const PATTERN = '/addEventListener\(\s*[\'"]alpine:init[\'"]\s*,\s*(\(|function\b|async\b)/';

    public function id(): string
    {
        return 'G';
    }

    public function category(): string
    {
        return 'alpine-init-only';
    }

    public function label(): string
    {
        return "Alpine components registered only in an anonymous alpine:init callback (copied before v3.6.2) — they are missing after a cross-layout wire:navigate; use `if (window.Alpine) { register(); } else { document.addEventListener('alpine:init', register); }`";
    }

    public function isAutoFixable(): bool
    {
        return false;
    }

    public function extensions(): array
    {
        return ['js', 'blade.php'];
    }

    public function matchesLine(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
