<?php

namespace VmEngine\Synapse\Services\Helper;

class Breadcrumbs
{
    protected $items = [];

    /**
     * Initialize Breadcrumbs with first item
     *
     * @param  string  $label
     * @param  string  $url
     * @param  string  $icon  - Icon class
     * @return self
     */
    public static function make($label, $url = '#', $icon = null)
    {
        return new self($label, $url, $icon);
    }

    public function __construct($label, $url = '#', $icon = null)
    {
        $this->add($label, $url, $icon);
    }

    /**
     * Add Breadcrumbs Item
     *
     * @param  string  $label
     * @param  string  $url
     * @param  string  $icon  - Icon class
     * @return self
     */
    public function add($label, $url = '#', $icon = null)
    {
        $this->items[] = [
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
        ];

        return $this;
    }

    public function hasItems()
    {
        return count($this->items) > 0;
    }

    public function items()
    {
        return $this->items;
    }

    public function count()
    {
        return count($this->items);
    }
}
