<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use ReflectionMethod;

class Password extends Component
{
    public function render()
    {
        return view('synapse::components.password');
    }
}
