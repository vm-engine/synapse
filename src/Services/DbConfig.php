<?php

namespace VmEngine\Synapse\Services;

use VmEngine\Synapse\Models\Config;

class DbConfig
{
    private array $cache = [];

    public function get($group, $key, $default = null, $force = false)
    {
        if (isset($this->cache[$group][$key]) && ! $force) {
            return $this->cache[$group][$key];
        }

        $this->cache[$group][$key] = Config::where('group', $group)->where('key', $key)->first()->value ?? $default;

        return $this->cache[$group][$key];
    }

    public function set($group, $key, $value, $label = null)
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
