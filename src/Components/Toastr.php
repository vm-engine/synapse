<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Toastr extends Component
{
    public function render()
    {
        return view('synapse::components.toastr');
    }
}
