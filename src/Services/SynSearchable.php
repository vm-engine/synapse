<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Laravel\Scout\Searchable as ScoutSearchableTrait;
use Throwable;
use VmEngine\Synapse\Models\ScoutSearchable;
use VmEngine\Synapse\Models\Searchable;

/**
 * Global backend search index.
 *
 * Opt-in: until `synapps:searchable-setup` has created the table, every
 * method is a silent no-op so modules can call put()/forget() unconditionally.
 */
class SynSearchable
{
    private const CONTENT_PREFIX = 'content:';

    private bool $enabled = false;

    public function isEnabled(): bool
    {
        // ponytail: only a positive result is memoized; while disabled each call
        // re-checks the schema (one cheap query). Memoize false too if that shows up.
        return $this->enabled = $this->enabled || Schema::hasTable('searchables');
    }

    /**
     * Insert or replace a content entry. A plain-string title/description is
     * stored once for every locale; an ['en' => ..., 'id' => ...] array stores
     * one row per locale.
     *
     * @param  string|array<string, string>  $title
     * @param  string|array<string, string>|null  $description
     * @param  array<string, mixed>  $routeParams
     */
    public function put(
        string $source,
        string $key,
        string|array $title,
        string|array|null $description = null,
        ?string $route = null,
        array $routeParams = [],
        ?string $url = null,
        ?string $icon = null,
        ?string $acl = null,
        ?string $keywords = null,
    ): void {
        $this->assertContentSource($source);

        if ($route === null && $url === null) {
            throw new InvalidArgumentException('A searchable entry needs a route or a url.');
        }

        if (! $this->isEnabled()) {
            return;
        }

        $perLocale = array_merge(is_array($title) ? $title : [], is_array($description) ? $description : []);
        $locales = $perLocale === [] ? [null] : array_keys($perLocale);

        $rows = [];
        foreach ($locales as $locale) {
            $rows[] = [
                'source' => $source,
                'key' => $key,
                'locale' => $locale,
                'title' => $this->pick($title, $locale),
                'description' => $description === null ? null : $this->pick($description, $locale),
                'icon' => $icon,
                'route' => $route,
                'route_params' => $routeParams === [] ? null : $routeParams,
                'url' => $url,
                'acl' => $acl,
                'keywords' => $keywords,
            ];
        }

        DB::transaction(function () use ($source, $key, $rows) {
            $this->deleteRows($this->query()->where('source', $source)->where('key', $key));

            foreach ($rows as $row) {
                $this->query()->create($row);
            }
        });
    }

    /**
     * Remove one content entry (all locales).
     */
    public function forget(string $source, string $key): void
    {
        $this->assertContentSource($source);

        if (! $this->isEnabled()) {
            return;
        }

        $this->deleteRows($this->query()->where('source', $source)->where('key', $key));
    }

    /**
     * Remove every content entry whose source starts with $prefix.
     * Prefixes outside "content:" are ignored (returns 0) so setup-owned
     * nav/module rows can never be wiped from here.
     */
    public function forgetSource(string $prefix): int
    {
        if (! str_starts_with($prefix, self::CONTENT_PREFIX) || ! $this->isEnabled()) {
            return 0;
        }

        return $this->deleteRows($this->query()->sourcePrefix($prefix));
    }

    /**
     * Search the current locale (plus locale-less rows), filtered by the
     * user's ACL and by whether the link still resolves.
     *
     * @return Collection<int, Searchable>
     */
    public function search(string $term, int $limit = 10): Collection
    {
        $term = trim($term);

        if ($term === '' || ! $this->isEnabled()) {
            return new Collection;
        }

        $user = auth()->user();
        $allowed = [];

        // ponytail: over-fetches 3x then filters in PHP; a user denied most matches
        // may get fewer than $limit results. Loop further pages if that bites.
        return $this->candidates($term, $limit * 3)
            ->filter(function (Searchable $row) use ($user, &$allowed) {
                if ($row->acl !== null) {
                    // Same check as @canAccess: synapps-auth overrides User::can() without Gate abilities.
                    $allowed[$row->acl] ??= $user instanceof Authorizable && $user->can($row->acl);

                    if (! $allowed[$row->acl]) {
                        return false;
                    }
                }

                return $row->resolveUrl() !== null;
            })
            ->take($limit)
            ->values();
    }

    /**
     * Replace all setup-owned rows (nav + module:*). Content rows are untouched.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function replaceSystemEntries(array $rows): int
    {
        return DB::transaction(function () use ($rows) {
            $this->deleteRows($this->query()->where(
                fn (Builder $query) => $query->where('source', 'nav')->orWhere(fn (Builder $query) => $query->sourcePrefix('module:'))
            ));

            foreach ($rows as $row) {
                $this->query()->create($row);
            }

            return count($rows);
        });
    }

    /**
     * @return Collection<int, Searchable>
     */
    private function candidates(string $term, int $take): Collection
    {
        if ($this->usesScout()) {
            try {
                return ScoutSearchable::candidates($term, $take);
            } catch (Throwable $e) {
                // Engine down (e.g. Typesense unreachable): keep the palette working.
                report($e);
            }
        }

        return $this->eloquentCandidates($term, $take);
    }

    /**
     * @return Collection<int, Searchable>
     */
    private function eloquentCandidates(string $term, int $take): Collection
    {
        $escaped = Searchable::escapeLike(mb_strtolower($term));
        $contains = '%'.$escaped.'%';

        return Searchable::query()
            ->where(fn (Builder $query) => $query->whereNull('locale')->orWhere('locale', app()->getLocale()))
            ->where(fn (Builder $query) => $query
                ->whereRaw("LOWER(title) LIKE ? ESCAPE '!'", [$contains])
                ->orWhereRaw("LOWER(description) LIKE ? ESCAPE '!'", [$contains])
                ->orWhereRaw("LOWER(keywords) LIKE ? ESCAPE '!'", [$contains]))
            ->orderByRaw(
                "CASE WHEN LOWER(title) LIKE ? ESCAPE '!' THEN 0 WHEN LOWER(title) LIKE ? ESCAPE '!' THEN 1 ELSE 2 END",
                [$escaped.'%', $contains]
            )
            ->orderBy('title')
            ->limit($take)
            ->get();
    }

    private function usesScout(): bool
    {
        return config('synapps.apps.searchable.engine') === 'scout'
            && trait_exists(ScoutSearchableTrait::class);
    }

    /**
     * @return class-string<Searchable>
     */
    private function modelClass(): string
    {
        return $this->usesScout() ? ScoutSearchable::class : Searchable::class;
    }

    /**
     * @return Builder<Searchable>
     */
    private function query(): Builder
    {
        return $this->modelClass()::query();
    }

    /**
     * Delete matching rows. Under Scout, delete model-by-model so the
     * engine index is updated through Scout's model events.
     *
     * @param  Builder<Searchable>  $query
     */
    private function deleteRows(Builder $query): int
    {
        if (! $this->usesScout()) {
            return (int) $query->delete();
        }

        // ponytail: loads every matching row; chunk if a forgetSource ever spans tens of thousands under Scout
        $rows = $query->get();
        $rows->each->delete();

        return $rows->count();
    }

    /**
     * @param  string|array<string, string>  $value
     */
    private function pick(string|array $value, ?string $locale): string
    {
        if (! is_array($value)) {
            return $value;
        }

        return $value[$locale] ?? (string) reset($value);
    }

    private function assertContentSource(string $source): void
    {
        if (! str_starts_with($source, self::CONTENT_PREFIX)) {
            throw new InvalidArgumentException(
                "Searchable source [{$source}] must start with \"content:\"; nav and module:* rows are owned by synapps:searchable-setup."
            );
        }
    }
}
