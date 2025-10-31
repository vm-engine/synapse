<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Toggler extends Component
{
    public function __construct(
        public string $id = '',
        public string $name = '',
        public string $label = '',
        public bool $checked = false,
        public string $activeColor = 'green',
        public bool $inline = false,
    ) {}

    public function render()
    {
        return view('synapse::components.toggler');
    }
}
