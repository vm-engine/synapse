<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Currency extends Component
{
    public function __construct(
        public string $currency = 'Rp.',
    ) {}

    public function render()
    {
        return view('synapse::components.currency');
    }
}
