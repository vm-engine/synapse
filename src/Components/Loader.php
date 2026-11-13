<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

/** Full-screen overlay shown while Livewire navigates or a request is in flight. Place once per layout. */
class Loader extends Component
{
    public function render()
    {
        return view('synapse::components.loader');
    }
}
