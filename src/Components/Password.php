<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Password extends Component
{
    public function render()
    {
        return view('synapse::components.password');
    }
}
