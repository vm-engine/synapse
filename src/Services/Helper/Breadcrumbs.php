<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\Helper;

class Breadcrumbs
{
    /** @var array<int, array{label: string, url: string, icon: string|null}> */
    protected array $items = [];

    /**
     * Initialize Breadcrumbs with first item
     *
     * @param  string  $icon  - Icon class
     */
    public static function make(string $label, string $url = '#', ?string $icon = null): self
    {
        return new self($label, $url, $icon);
    }

    public function __construct(string $label, string $url = '#', ?string $icon = null)
    {
        $this->add($label, $url, $icon);
    }

    /**
     * Add Breadcrumbs Item
     *
     * @param  string  $icon  - Icon class
     */
    public function add(string $label, string $url = '#', ?string $icon = null): self
    {
        $this->items[] = [
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
        ];

        return $this;
    }

    public function hasItems(): bool
    {
        return count($this->items) > 0;
    }

    /** @return array<int, array{label: string, url: string, icon: string|null}> */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
