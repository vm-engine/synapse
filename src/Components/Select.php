<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use ReflectionMethod;

class Select extends Component
{
    public function __construct(
        public array $options,
    ) {}

    public function render()
    {
        return view('synapse::components.select');
    }
}
