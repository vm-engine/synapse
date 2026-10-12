<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Nav;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Counts for menu badges: resolves the badge class, caches its count, and forgets it on demand.
 *
 * Cache key = prefix : global version : class hash : class version : scope. Bumping a version
 * orphans every key under it, so "refresh for everyone" works on any cache store (no tags needed).
 */
final class NavBadgeRepository
{
    private const PREFIX = 'synapse.nav-badge';

    /** @var array<string, int> counts already resolved in this request, keyed by class|scope */
    private array $memo = [];

    /** @var array<string, true> badge classes already reported as broken in this request */
    private array $reported = [];

    /** The count of a badge class for the current scope; 0 when the class is unusable. */
    public function count(string $class): int
    {
        try {
            if (! is_subclass_of($class, NavBadge::class)) {
                throw new InvalidArgumentException("{$class} is not a ".NavBadge::class);
            }

            $badge = app($class);
            $scope = $badge->scope();
            $memoKey = $class.'|'.$scope;
            if (isset($this->memo[$memoKey])) {
                return $this->memo[$memoKey];
            }

            $ttl = $badge->ttl();
            if ($ttl === 0) {
                return $this->memo[$memoKey] = max(0, (int) $badge->count());
            }

            $key = $this->key($class, $scope);
            $cached = Cache::get($key);
            if (! is_int($cached)) {
                $cached = max(0, (int) $badge->count());
                $ttl === null ? Cache::forever($key, $cached) : Cache::put($key, $cached, $ttl);
            }

            return $this->memo[$memoKey] = $cached;
        } catch (Throwable $exception) {
            // A badge must never take the menu (and with it every backend page) down.
            if (! isset($this->reported[$class])) {
                $this->reported[$class] = true;
                Log::warning("Nav badge [{$class}] skipped: ".$exception->getMessage());
            }

            return 0;
        }
    }

    /** Forget a badge's cached count: for one scope, or (null) for everyone. */
    public function refresh(string $class, int|string|null $scope = null): void
    {
        $this->afterCommit(function () use ($class, $scope): void {
            if ($scope === null) {
                Cache::forever($this->versionKey($class), $this->version($class) + 1);
            } else {
                Cache::forget($this->key($class, (string) $scope));
            }
        });

        $this->memo = array_filter($this->memo, fn (string $key): bool => ! str_starts_with($key, $class.'|'), ARRAY_FILTER_USE_KEY);
    }

    /** Forget every badge's cached count. */
    public function refreshAll(): void
    {
        $this->afterCommit(fn () => Cache::forever(self::PREFIX.'.version', $this->globalVersion() + 1));
        $this->memo = [];
    }

    /**
     * Listen for what each declared badge says makes it stale (NavBadge::refreshOn()).
     * Registered for every badge in the menu config, whoever the current user is: the person
     * who causes a change is often not the one who sees the badge.
     */
    public function registerListeners(): void
    {
        foreach ($this->declaredClasses((array) config('synapps.nav.backend', [])) as $class) {
            if (! is_subclass_of($class, NavBadge::class)) {
                continue;
            }

            foreach ($class::refreshOn() as $target) {
                $events = is_subclass_of($target, Model::class)
                    ? ["eloquent.saved: {$target}", "eloquent.deleted: {$target}"]
                    : [$target];

                // Resolved when the event fires: the repository is request-scoped, listeners are not.
                Event::listen($events, fn () => app(self::class)->refresh($class));
            }
        }
    }

    /**
     * @param  array<int|string, mixed>  $items
     * @return list<string> distinct "badge" values found anywhere in the menu tree
     */
    private function declaredClasses(array $items): array
    {
        $classes = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (is_string($item['badge'] ?? null)) {
                $classes[] = $item['badge'];
            }
            if (is_array($item['sub'] ?? null)) {
                $classes = [...$classes, ...$this->declaredClasses($item['sub'])];
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * Run now, or when the open database transaction commits (never, if it rolls back). Forgetting
     * earlier would let another request recount before the change is visible and cache the old number.
     *
     * @param  Closure(): mixed  $forget
     */
    private function afterCommit(Closure $forget): void
    {
        app()->bound('db.transactions') ? app('db.transactions')->addCallback($forget) : $forget();
    }

    private function key(string $class, string $scope): string
    {
        return implode(':', [self::PREFIX, $this->globalVersion(), md5($class), $this->version($class), $scope]);
    }

    private function versionKey(string $class): string
    {
        return self::PREFIX.'.version.'.md5($class);
    }

    private function version(string $class): int
    {
        return (int) Cache::get($this->versionKey($class), 0);
    }

    private function globalVersion(): int
    {
        return (int) Cache::get(self::PREFIX.'.version', 0);
    }
}
