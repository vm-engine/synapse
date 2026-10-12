<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Nav;

/**
 * A count shown beside a backend menu item. Name the class in the item's "badge" key in module.json:
 *
 *   "pending": { "id": "pending", "route": "…", "acl": "…", "badge": "Vendor\\Module\\Nav\\PendingBadge" }
 *
 * The class is resolved from the container. count() runs at most once per cache lifetime, so it
 * may be a query — but keep it to a single fast one: an uncached badge runs on every backend page.
 */
abstract class NavBadge
{
    /** The number to show; null or 0 shows nothing. */
    abstract public function count(): ?int;

    /**
     * What makes the cached count stale: Eloquent model classes (any save or delete) and/or
     * event classes (when fired). Bulk queries do not fire model events — call
     * synav()->refreshBadge(static::class) after those.
     *
     * @return list<class-string>
     */
    public static function refreshOn(): array
    {
        return [];
    }

    /** Seconds the count may stay cached when nothing refreshes it first; 0 = never cache, null = until refreshed. */
    public function ttl(): ?int
    {
        return 3600;
    }

    /** Cache variant for counts that differ per viewer, e.g. (string) auth()->id(); '' = one shared count. */
    public function scope(): string
    {
        return '';
    }
}
