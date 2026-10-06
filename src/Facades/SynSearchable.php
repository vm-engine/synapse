<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static void put(string $source, string $key, string|array<string, string> $title, string|array<string, string>|null $description = null, ?string $route = null, array<string, mixed> $routeParams = [], ?string $url = null, ?string $icon = null, ?string $acl = null, ?string $keywords = null)
 * @method static void forget(string $source, string $key)
 * @method static int forgetSource(string $prefix)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \VmEngine\Synapse\Models\Searchable> search(string $term, int $limit = 10)
 *
 * @see \VmEngine\Synapse\Services\SynSearchable
 */
class SynSearchable extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'synsearchable';
    }
}
