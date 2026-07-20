<?php

namespace VmEngine\Synapse\Services\Upgrade\Rules;

use VmEngine\Synapse\Services\Upgrade\AbstractUpgradeRule;

/**
 * $this->registerMfcComponents(); $this->registerLivewireComponents(); (removed in v2.2.0)
 * -> $this->registerLivewireNamespace();
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php (rule C).
 */
class RegisterMethodsRule extends AbstractUpgradeRule
{
    private const PATTERN = '/\$this->(registerMfcComponents|registerLivewireComponents)\(\s*\)\s*;/';

    private const PAIRED_PATTERN = '/\$this->registerMfcComponents\(\s*\)\s*;\s*\n?\s*\$this->registerLivewireComponents\(\s*\)\s*;/';

    public function id(): string
    {
        return 'C';
    }

    public function category(): string
    {
        return 'register-methods';
    }

    public function label(): string
    {
        return 'AutoRegistersComponents::registerMfcComponents()/registerLivewireComponents() (removed in v2.2.0) — replace with registerLivewireNamespace()';
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
        // Collapse the adjacent pair into a single call first, then catch any
        // remaining lone occurrence of either method.
        $contents = preg_replace(self::PAIRED_PATTERN, '$this->registerLivewireNamespace();', $contents);

        return preg_replace(self::PATTERN, '$this->registerLivewireNamespace();', $contents);
    }
}
