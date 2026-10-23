<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

/** Centered "nothing here" block for lists and panels; the slot holds an optional action. */
class EmptyState extends Component
{
    public function __construct(
        public string $title,
        public ?string $text = null,
        public string $icon = 'ph ph-tray',
    ) {}

    public function render()
    {
        return view('synapse::components.empty-state');
    }
}
