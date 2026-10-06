<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Lightbox extends Component
{
    public function __construct(
        public string $name = 'lightbox',
    ) {}

    public function render()
    {
        return view('synapse::components.lightbox');
    }
}
