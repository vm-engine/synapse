<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;

class Drawer extends Component
{
    /** Panel id; the title is `{id}-title`. */
    public string $drawerId;

    public function __construct(
        /** Raw HTML (icon markup is passed in) — never put user input in it. */
        public string $title,
        /** Opens/closes by `open-drawer-{name}` / `close-drawer-{name}`; null = parent's `drawerOpen`. */
        public ?string $name = null,
    ) {
        $this->drawerId = 'drawer-'.($name ?? Str::lower(Str::random(8)));
    }

    public function render()
    {
        return view('synapse::components.drawer');
    }
}
