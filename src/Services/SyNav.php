<?php

namespace VmEngine\Synapse\Services;

use Illuminate\Support\Str;

/**
 * Synapse Navigation Helper
 */
class SyNav
{
    protected string $activeMenu = '';

    public function backend()
    {
        return config('synapps.nav.backend');
    }

    /**
     * Get generated Backend Navigation Menu
     *
     * @return array
     */
    public function frontend()
    {
        return config('synapps.nav.frontend');
    }

    /**
     * Translate given string name if start with '__' or else return as is
     *
     * @return string
     */
    public function translate(string $name)
    {
        return Str::startsWith($name, '__') ? __(substr($name, 2)) : $name;
    }

    /**
     * Set Current active menu
     *
     * @return void
     */
    public function setActiveMenu(string $id)
    {
        $this->activeMenu = $id;
    }

    /**
     * Get Active Menu
     *
     * @return string
     */
    public function getActiveMenu()
    {
        return $this->activeMenu;
    }

    public function is(string $id)
    {
        return Str::startsWith($this->activeMenu, $id);
    }
}
