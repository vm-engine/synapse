<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Modal extends Component
{
    public function __construct(
        public string $name = 'modal',
        public string $maxWidth = 'xl',
        public bool $show = false,
        public ?string $title = null,
    ) {}

    public function render()
    {
        return view('synapse::components.modal');
    }
}
