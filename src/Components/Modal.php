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
        /** Id of a heading the content renders; labels the dialog without drawing a header. */
        public ?string $labelledby = null,
    ) {}

    public function render()
    {
        return view('synapse::components.modal');
    }
}
