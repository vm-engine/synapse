<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Checkboxes extends Component
{
    public function __construct(
        public string $name,
        public array $options,
        public array $model = [],
    ) {}

    public function render()
    {
        return view('synapse::components.checkboxes');
    }
}
