<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use ReflectionMethod;

class Toastr extends Component
{
	public function render()
    {
        return view('synapse::components.toastr');
    }
}
