<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;

/**
 * One searchable entry in one locale (locale null = every locale).
 *
 * @property int $id
 * @property string $source nav | module:{id} | content:{anything}
 * @property string $key unique within source (per locale)
 * @property string|null $locale
 * @property string $title
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $route backend-relative route name (same as nav)
 * @property array<string, mixed>|null $route_params
 * @property string|null $url used when route is null
 * @property string|null $acl Gate ability; null = any backend user
 * @property string|null $keywords
 */
class Searchable extends Model
{
    protected $table = 'searchables';

    protected $fillable = [
        'source',
        'key',
        'locale',
        'title',
        'description',
        'icon',
        'route',
        'route_params',
        'url',
        'acl',
        'keywords',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'route_params' => 'array',
        ];
    }

    /**
     * Rows whose source starts with the given literal prefix.
     *
     * @param  Builder<static>  $query
     */
    public function scopeSourcePrefix(Builder $query, string $prefix): void
    {
        $query->whereRaw("source LIKE ? ESCAPE '!'", [static::escapeLike($prefix).'%']);
    }

    /**
     * Escape LIKE wildcards using "!" — a portable escape char across
     * MySQL, PostgreSQL and SQLite (backslash is not portable).
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /**
     * Resolve the link, trying the backend-prefixed route name first.
     * Returns null when the route no longer exists or its params don't fit,
     * so stale rows (module removed, setup not re-run) are skipped, not 500s.
     */
    public function resolveUrl(): ?string
    {
        if ($this->route === null) {
            return $this->safeUrl();
        }

        $prefix = config('synapps.auth.routes.backend_name_prefix', 'backend');

        foreach ([$prefix.'.'.$this->route, $this->route] as $name) {
            if (Route::has($name)) {
                try {
                    return route($name, $this->route_params ?? []);
                } catch (UrlGenerationException) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * The stored url, only if it's app-relative or http(s). Blocks javascript:,
     * data: and protocol-relative links, since content urls may originate from
     * user data and are rendered as clickable hrefs.
     */
    private function safeUrl(): ?string
    {
        $url = $this->url;

        if ($url === null) {
            return null;
        }

        $isRelative = str_starts_with($url, '/') && ! str_starts_with($url, '//');

        return $isRelative || preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }
}
