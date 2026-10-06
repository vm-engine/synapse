<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Searchable as ScoutIndexable;

/**
 * Scout-indexed variant of Searchable, same table.
 *
 * Only referenced when config('synapps.apps.searchable.engine') === 'scout'
 * AND laravel/scout is installed, so this file is never autoloaded otherwise.
 */
class ScoutSearchable extends Searchable
{
    use ScoutIndexable;

    public function searchableAs(): string
    {
        return 'searchables';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'locale' => $this->locale,
        ];
    }

    /**
     * Engine-ranked candidates for the current locale (plus locale-less rows).
     *
     * @return Collection<int, Searchable>
     */
    public static function candidates(string $term, int $take): Collection
    {
        $locale = app()->getLocale();

        // ponytail: locale filtered after the engine's take(); swap to an engine
        // filter (e.g. Typesense filter_by) if one locale crowds out the other.
        return static::search($term)->take($take)->get()
            ->filter(fn (Searchable $row) => $row->locale === null || $row->locale === $locale)
            ->values();
    }
}
