<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Drawer extends Component
{
    public function __construct(
        public string $title
    ) {}

    public function render()
    {
        return view('synapse::components.drawer');
    }
}
