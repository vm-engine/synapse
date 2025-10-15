<?php

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
     * @param  type  $value  yang akan dicek (float/double)
     * @return bool
     */
    function is_decimal($value)
    {
        // return ((float) $value !== floor($value));
        return is_numeric($value) && floor($value) != $value;
    }
}
