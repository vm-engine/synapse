<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Datepicker extends Component
{
    public function __construct(
        public string $flatpickr = '{}'
    ) {}

    public function render()
    {
        return view('synapse::components.datepicker');
    }
}
