<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Count badge for a backend menu item: <x-synapse-nav-badge :item="$item" />.
 * Renders nothing when the item (and its visible children) has nothing to count.
 */
class NavBadge extends Component
{
    /** @param  array<string, mixed>  $item  the menu item from config('synapps.nav.backend') */
    public function __construct(public array $item) {}

    public function label(): string
    {
        return synav()->badgeLabel($this->item);
    }

    public function render(): View
    {
        return view('synapse::components.nav-badge');
    }
}
