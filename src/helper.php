<?php

use Illuminate\Support\Str;

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

if (! function_exists('synapps_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param  string  $path
     * @return string
     */
    function synapps_path($path = '')
    {
        return base_path('synapps'.($path ? DS.$path : $path));
    }
}

if (! function_exists('is_decimal')) {
    /**
     *  mengecek apakah sebuah nilai angka mengandung decimal atau tidak
     *
     * @param  mixed  $value  yang akan dicek (float/double)
     * @return bool
     */
    function is_decimal($value)
    {
        // return ((float) $value !== floor($value));
        return is_numeric($value) && floor($value) != $value;
    }
}

if (! function_exists('human_filesize')) {
    /**
     * Convert bytes to human readable filesize
     *
     * @param  int  $bytes  Size in bytes
     * @param  int  $decimals  Number of decimals
     * @return string
     */
    function human_filesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = (int) floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).' '.@$sz[$factor].'B';
    }
}

if (! function_exists('synav')) {
    /**
     * SyNav Helper function
     *
     * @return VmEngine\Synapse\Services\SyNav
     */
    function synav()
    {
        return app('synav');
    }
}

if (! function_exists('dbconf')) {
    /**
     * Get Config from database
     *
     * @param  string|array  $configString  String to get config, e.g.: 'group.key', Add ! prefix to skip config cache
     *                                      Associative Array to set config ['group.key' => 'value']
     * @param  mixed  $default  Default value
     * @param  bool  $force  Force get from database directly
     */
    function dbconf(string|array $configString, $default = null, $force = false): mixed
    {
        $config = app('dbconf');
        if (is_string($configString)) {
            if (Str::startsWith($configString, '!')) {
                $force = true;
                $configString = Str::after($configString, '!');
            }
            [$group, $key] = explode('.', $configString, 2);

            return $config->get($group, $key, $default, $force);
        } else {
            $configKey = key($configString);
            [$group, $key] = explode('.', (string) $configKey, 2);
            $value = current($configString);

            return $config->set($group, $key, $value);
        }
    }
}

if (! function_exists('___')) {
    /**
     * Get Language from Database
     *
     * @param  string  $key  Translation key
     * @param  string|null  $languageCode  Optional language code (defaults to current language)
     */
    function ___(string $key, ?string $languageCode = null): string
    {
        return app('dblang')->get($key, $languageCode);
    }
}

if (! function_exists('page_title')) {
    /**
     * Add Title to Main App Title
     *
     * @param  string  $title
     * @param  string  $separator
     */
    function page_title($title, $separator = '-'): string
    {
        if (is_array($title)) {
            $title = implode(' '.$separator.' ', $title);
        }

        return $title.' '.$separator.' '.config('synapps.apps.title', config('app.name'));
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Get URL to theme asset
     *
     * @param  string  $path  Path relative to theme assets folder (e.g., 'images/logo.svg')
     * @param  string|null  $theme  Theme name (null = active theme)
     */
    function theme_asset(string $path, ?string $theme = null): string
    {
        $theme = $theme ?? config('synapps.apps.themes.active', 'default');
        $path = ltrim($path, '/');

        return asset("assets/themes/{$theme}/{$path}");
    }
}

if (! function_exists('theme_path')) {
    /**
     * Get absolute path to theme directory
     *
     * @param  string  $path  Path relative to theme folder
     * @param  string|null  $theme  Theme name (null = active theme)
     */
    function theme_path(string $path = '', ?string $theme = null): string
    {
        $theme = $theme ?? config('synapps.apps.themes.active', 'default');

        return synapps_path('resources/themes/'.$theme.($path ? '/'.ltrim($path, '/') : ''));
    }
}

if (! function_exists('active_theme')) {
    /**
     * Get active theme name
     */
    function active_theme(): string
    {
        return config('synapps.apps.themes.active', 'default');
    }
}

if (! function_exists('theme_service')) {
    /**
     * Get ThemeService instance
     */
    function theme_service(): \VmEngine\Synapse\Services\ThemeService
    {
        return app('synapse.theme');
    }
}
