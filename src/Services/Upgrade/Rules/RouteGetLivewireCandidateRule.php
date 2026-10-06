<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Upgrade\Rules;

use VmEngine\Synapse\Services\Upgrade\AbstractUpgradeRule;

/**
 * Route::get('/x', Dashboard::class) candidate (v2.0.0 introduced Route::livewire()).
 *
 * Not auto-fixable: not every Route::get(...) targeting a ::class reference is a
 * Livewire full-page component — this only flags the pattern for manual review.
 *
 * Sibling implementation: packages/synapse/tools/synapse-upgrade-audit.php (rule E).
 */
class RouteGetLivewireCandidateRule extends AbstractUpgradeRule
{
    private const PATTERN = '/Route::get\(\s*[\'"][^\'"]+[\'"]\s*,\s*([A-Za-z0-9_\\\\]+)::class\s*\)/';

    public function id(): string
    {
        return 'E';
    }

    public function category(): string
    {
        return 'route-get-livewire-candidate';
    }

    public function label(): string
    {
        return 'Route::get(SomeComponent::class) candidate (v2.0.0 introduced Route::livewire()) — review whether this should be Route::livewire()';
    }

    public function isAutoFixable(): bool
    {
        return false;
    }

    public function extensions(): array
    {
        return ['php'];
    }

    public function matchesLine(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
