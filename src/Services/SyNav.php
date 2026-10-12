<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

use Closure;
use Illuminate\Support\Str;
use VmEngine\Synapse\Nav\NavBadgeRepository;

/**
 * Synapse Navigation Helper
 *
 * @phpstan-type BadgeAuthorizer Closure(string): bool
 */
class SyNav
{
    protected string $activeMenu = '';

    /** @var BadgeAuthorizer|null */
    protected ?Closure $badgeAuthorizer = null;

    public function backend(): mixed
    {
        return config('synapps.nav.backend');
    }

    /**
     * Get generated Frontend Navigation Menu
     */
    public function frontend(): mixed
    {
        return config('synapps.nav.frontend');
    }

    /**
     * Translate given string name if start with '__' or else return as is
     */
    public function translate(string $name): string
    {
        return Str::startsWith($name, '__') ? __(substr($name, 2)) : $name;
    }

    /**
     * Set Current active menu
     *
     * @return void
     */
    public function setActiveMenu(string $id)
    {
        $this->activeMenu = $id;
    }

    /**
     * Get Active Menu
     */
    public function getActiveMenu(): string
    {
        return $this->activeMenu;
    }

    public function is(string $id): bool
    {
        return Str::startsWith($this->activeMenu, $id);
    }

    /**
     * Count for a menu item: its own badge plus those of the children the current user may see.
     *
     * @param  array<string, mixed>  $item  a menu item as found in config('synapps.nav.backend')
     */
    public function badge(array $item): int
    {
        $count = is_string($item['badge'] ?? null) ? app(NavBadgeRepository::class)->count($item['badge']) : 0;

        foreach ((array) ($item['sub'] ?? []) as $child) {
            if (is_array($child) && $this->mayShowBadge($child['acl'] ?? null)) {
                $count += $this->badge($child);
            }
        }

        return $count;
    }

    /**
     * What the badge shows: '' for nothing, the number up to 99, then "99+".
     *
     * @param  array<string, mixed>  $item
     */
    public function badgeLabel(array $item): string
    {
        $count = $this->badge($item);

        return match (true) {
            $count <= 0 => '',
            $count > 99 => '99+',
            default => (string) $count,
        };
    }

    /**
     * Forget a badge's cached count so the next page recounts: for everyone, or for one scope.
     *
     * @param  class-string  $badgeClass
     */
    public function refreshBadge(string $badgeClass, int|string|null $scope = null): void
    {
        app(NavBadgeRepository::class)->refresh($badgeClass, $scope);
    }

    /** Forget every badge's cached count (after bulk changes, imports, resets). */
    public function refreshBadges(): void
    {
        app(NavBadgeRepository::class)->refreshAll();
    }

    /**
     * Replace the permission check used when summing children (tests, custom auth).
     * The callback receives the child's "acl" string ('' when it has none).
     *
     * @param  BadgeAuthorizer|null  $callback
     */
    public function authorizeBadgesUsing(?Closure $callback): void
    {
        $this->badgeAuthorizer = $callback;
    }

    /** Same rule as the sidebar's @canAccess: can_access() when an auth package provides it. */
    protected function mayShowBadge(mixed $acl): bool
    {
        $acl = is_string($acl) ? $acl : '';

        if ($this->badgeAuthorizer !== null) {
            return ($this->badgeAuthorizer)($acl);
        }

        return ! function_exists('can_access') || can_access($acl === '' ? null : $acl);
    }
}
