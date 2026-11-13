<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

/** Left-hand title (and optional help) of a two-column form section. */
class SectionHeading extends Component
{
    public function __construct(
        public string $title,
        public ?string $help = null,
    ) {}

    public function render()
    {
        return view('synapse::components.section-heading');
    }
}
