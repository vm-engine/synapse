<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Panel extends Component
{
    public function __construct(
        public ?string $title = null,
        public bool $collapsible = false,
        public bool $collapsed = false,
    ) {}

    public function render()
    {
        return view('synapse::components.panel');
    }
}
