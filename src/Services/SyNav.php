<?php

namespace VmEngine\Synapse\Services;

use Illuminate\Support\Str;

/**
 * Synapse Navigation Helper
 */
class SyNav
{
    protected string $activeMenu = '';

    public function backend(): mixed
    {
        return config('synapps.nav.backend');
    }

    /**
     * Get generated Frontend Navigation Menu
     */
    public function frontend(): mixed
    {
        return config('synapps.nav.frontend');
    }

    /**
     * Translate given string name if start with '__' or else return as is
     */
    public function translate(string $name): string
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
     */
    public function getActiveMenu(): string
    {
        return $this->activeMenu;
    }

    public function is(string $id): bool
    {
        return Str::startsWith($this->activeMenu, $id);
    }
}
