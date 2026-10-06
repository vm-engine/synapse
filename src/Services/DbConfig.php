<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

use VmEngine\Synapse\Models\Config;

class DbConfig
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    public function get(string $group, string $key, mixed $default = null, bool $force = false): mixed
    {
        if (isset($this->cache[$group][$key]) && ! $force) {
            return $this->cache[$group][$key];
        }

        $this->cache[$group][$key] = Config::where('group', $group)->where('key', $key)->first()->value ?? $default;

        return $this->cache[$group][$key];
    }

    public function set(string $group, string $key, mixed $value, ?string $label = null): mixed
    {
        $data = ['value' => $value];

        if ($label !== null) {
            $data['label'] = $label;
        }

        Config::updateOrCreate(
            ['group' => $group, 'key' => $key],
            $data
        );

        $this->cache[$group][$key] = $value;

        return $value;
    }
}
