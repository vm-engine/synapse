<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Select extends Component
{
    /** @param  array<int|string, mixed>  $options */
    public function __construct(
        public array $options,
    ) {}

    public function render()
    {
        return view('synapse::components.select');
    }
}
