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
        $factor = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;

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
     * @param  string|array  $title
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

if (! function_exists('int2roman')) {
    /**
     * Convert Integer to Roman Numerical
     */
    function int2roman(int $integer): string
    {
        // Create a lookup array that contains all of the Roman numerals and their values
        $lookup = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        $result = '';
        // Ensure the input is an integer
        $integer = intval($integer);

        foreach ($lookup as $roman => $value) {
            // Determine the number of matches
            $matches = intval($integer / $value);
            // Add the same number of characters to the result string
            $result .= str_repeat($roman, $matches);
            // Set the integer to be the remainder
            $integer = $integer % $value;
        }

        return $result;
    }
}
